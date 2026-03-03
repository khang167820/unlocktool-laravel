<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SeoAnalyzerService;

class SeoRedirect
{
    /**
     * Handle incoming request — check for 301/302 redirects
     */
    public function handle(Request $request, Closure $next)
    {
        $path = '/' . ltrim($request->path(), '/');
        
        $redirect = SeoAnalyzerService::processRedirect($path);
        
        if ($redirect) {
            $toUrl = $redirect['to_url'];
            
            // Make relative URLs absolute
            if (strpos($toUrl, 'http') !== 0) {
                $toUrl = url($toUrl);
            }
            
            return redirect($toUrl, $redirect['status_code']);
        }
        
        return $next($request);
    }
}
