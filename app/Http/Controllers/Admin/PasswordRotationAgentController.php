<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordRotationAgentController extends Controller
{
    private const SERVICE_TYPE = 'Unlocktool';

    /**
     * Tạo token mới cho agent (admin action).
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        $token = Str::random(64);

        // Thu hồi mọi token cũ
        DB::table('password_rotation_agents')
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        DB::table('password_rotation_agents')->insert([
            'name' => trim($data['name'] ?? '') ?: 'Agent Windows',
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('agent_token', $token)->with('success', 'Đã tạo mã kết nối cho agent Windows. Sao chép mã này một lần vào tool.');
    }

    /**
     * Đưa danh sách account vào hàng đợi đổi pass tự động.
     */
    public function queue(Request $request)
    {
        $data = $request->validate([
            'account_ids' => ['required', 'array', 'min:1', 'max:100'],
            'account_ids.*' => ['integer'],
        ]);

        $queued = 0;
        $skipped = 0;

        foreach (array_unique($data['account_ids']) as $accountId) {
            $account = DB::table('accounts')
                ->where('id', $accountId)
                ->where('type', self::SERVICE_TYPE)
                ->where('password_changed', 0)
                ->first();

            if (! $account) {
                $skipped++;
                continue;
            }

            // ⛔ Bỏ qua nếu khách còn thuê > 40 phút (dưới 40p thì cho đổi)
            $hasActiveRental = DB::table('orders')
                ->where('account_id', $accountId)
                ->whereIn('status', ['paid', 'completed'])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now()->addMinutes(40))
                ->exists();

            if ($hasActiveRental) {
                $skipped++;
                continue;
            }

            // Sinh mật khẩu mới nếu chưa có
            $newPassword = $account->new_password ?: 'Unlock'.random_int(100, 999);
            if (empty($account->new_password)) {
                DB::table('accounts')->where('id', $account->id)->update(['new_password' => $newPassword]);
            }

            // Kiểm tra job đã tồn tại chưa
            $existing = DB::table('password_rotation_jobs')->where('account_id', $account->id)->first();
            if ($existing && $existing->status === 'queued') {
                $skipped++;
                continue;
            }
            // Bỏ qua job đang xử lý (lock chưa hết)
            if ($existing && $existing->status === 'processing' && $existing->locked_until && $existing->locked_until > now()->toDateTimeString()) {
                $skipped++;
                continue;
            }

            $job = [
                'service_type' => self::SERVICE_TYPE,
                'status' => 'queued',
                'attempts' => 0,
                'agent_id' => null,
                'locked_until' => null,
                'last_message' => null,
                'started_at' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('password_rotation_jobs')->where('id', $existing->id)->update($job);
            } else {
                DB::table('password_rotation_jobs')->insert($job + [
                    'account_id' => $account->id,
                    'created_at' => now(),
                ]);
            }

            $queued++;
        }

        return back()->with('success', "Đã thêm {$queued} tài khoản UnlockTool vào hàng đợi tự động." . ($skipped ? " Bỏ qua {$skipped} tài khoản đang được xử lý hoặc không còn hợp lệ." : ''));
    }

    /**
     * API: Agent poll lấy job tiếp theo.
     */
    public function poll(Request $request): JsonResponse
    {
        $agent = $this->authenticateAgent($request);

        $payload = DB::transaction(function () use ($agent) {
            // Giải phóng job bị agent mất kết nối (lock hết hạn)
            DB::table('password_rotation_jobs')
                ->where('status', 'processing')
                ->where('locked_until', '<', now())
                ->update([
                    'status' => 'queued',
                    'agent_id' => null,
                    'locked_until' => null,
                    'last_message' => 'Agent mất kết nối, đã đưa lại vào hàng đợi.',
                    'updated_at' => now(),
                ]);

            // Lấy job tiếp theo
            $job = DB::table('password_rotation_jobs')
                ->where('service_type', self::SERVICE_TYPE)
                ->where('status', 'queued')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $account = DB::table('accounts')->where('id', $job->account_id)->first();
            if (! $account || $account->type !== self::SERVICE_TYPE || $account->password_changed || empty($account->new_password)) {
                DB::table('password_rotation_jobs')->where('id', $job->id)->update([
                    'status' => 'cancelled',
                    'last_message' => 'Tài khoản không còn đủ điều kiện đổi mật khẩu.',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

                return null;
            }

            // ⛔ Bỏ qua nếu khách còn thuê > 40 phút
            $hasActiveRental = DB::table('orders')
                ->where('account_id', $job->account_id)
                ->whereIn('status', ['paid', 'completed'])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now()->addMinutes(40))
                ->exists();

            if ($hasActiveRental) {
                DB::table('password_rotation_jobs')->where('id', $job->id)->update([
                    'status' => 'cancelled',
                    'last_message' => 'Bỏ qua: khách còn thuê hơn 40 phút.',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

                return null;
            }

            // Lock job cho agent
            DB::table('password_rotation_jobs')->where('id', $job->id)->update([
                'status' => 'processing',
                'agent_id' => $agent->id,
                'attempts' => $job->attempts + 1,
                'locked_until' => now()->addMinutes(15),
                'started_at' => now(),
                'last_message' => 'Agent đang đổi mật khẩu.',
                'updated_at' => now(),
            ]);

            return [
                'id' => $job->id,
                'username' => $account->username,
                'current_password' => $account->password,
                'new_password' => $account->new_password,
            ];
        });

        return response()->json(['job' => $payload]);
    }

    /**
     * API: Agent báo cáo kết quả.
     */
    public function report(Request $request, int $jobId): JsonResponse
    {
        $agent = $this->authenticateAgent($request);

        $data = $request->validate([
            'status' => ['required', 'in:processing,attention,failed,success'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $job = DB::table('password_rotation_jobs')
            ->where('id', $jobId)
            ->where('agent_id', $agent->id)
            ->first();

        if (! $job) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy tác vụ của agent này.'], 404);
        }

        $message = trim(strip_tags($data['message'] ?? ''));

        if ($data['status'] === 'success') {
            $result = $this->completeAccountRotation($job->account_id);

            DB::table('password_rotation_jobs')->where('id', $job->id)->update([
                'status' => 'succeeded',
                'locked_until' => null,
                'last_message' => $result['message'],
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => $result['message']]);
        }

        $status = $data['status'] === 'failed' ? 'failed' : $data['status'];
        DB::table('password_rotation_jobs')->where('id', $job->id)->update([
            'status' => $status,
            'locked_until' => $status === 'processing' ? now()->addMinutes(15) : null,
            'last_message' => $message ?: match ($status) {
                'attention' => 'Cần xác minh Turnstile trên máy chạy agent.',
                'failed' => 'Agent không thể đổi mật khẩu.',
                default => 'Agent đang xử lý.',
            },
            'completed_at' => $status === 'failed' ? now() : null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Xác thực Bearer token từ agent.
     */
    private function authenticateAgent(Request $request): object
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && strlen($token) >= 32, 401);

        $agent = DB::table('password_rotation_agents')
            ->where('token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();

        abort_unless($agent, 401);

        DB::table('password_rotation_agents')->where('id', $agent->id)->update([
            'last_seen_at' => now(),
            'updated_at' => now(),
        ]);

        return $agent;
    }

    /**
     * Khi agent báo đổi pass thành công → cập nhật DB.
     */
    private function completeAccountRotation(int $accountId): array
    {
        $account = DB::table('accounts')->where('id', $accountId)->first();
        abort_unless($account && ! empty($account->new_password), 422, 'Tài khoản không còn mật khẩu mới.');

        // Kiểm tra đơn thuê đang active
        $activeOrder = DB::table('orders')
            ->where('account_id', $accountId)
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();

        $updates = [
            'password' => $account->new_password,
            'new_password' => null,
            // ⛔ KHÔNG đánh dấu password_changed nếu tài khoản đang cho thuê
            // Nếu có đơn active → giữ password_changed=0 để khách vẫn thấy pass mới
            'password_changed' => $activeOrder ? 0 : 1,
            'needs_password_sync' => 0,
            'password_synced_at' => now(),
            'is_available' => $activeOrder ? 0 : 1,
        ];

        DB::table('accounts')->where('id', $accountId)->update($updates);

        return [
            'message' => $activeOrder
                ? 'Đã đổi mật khẩu; tài khoản vẫn đang có đơn thuê còn hiệu lực.'
                : 'Đã đổi mật khẩu và chuyển tài khoản sang chờ thuê.',
        ];
    }
}
