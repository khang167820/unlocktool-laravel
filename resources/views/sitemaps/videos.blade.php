{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        <video:video>
            <video:thumbnail_loc>{{ $entry['thumbnail'] }}</video:thumbnail_loc>
            <video:title>{{ htmlspecialchars($entry['title'], ENT_XML1) }}</video:title>
            <video:description>{{ htmlspecialchars($entry['description'], ENT_XML1) }}</video:description>
            <video:content_loc>{{ $entry['content_loc'] }}</video:content_loc>
            <video:player_loc>{{ $entry['player_loc'] }}</video:player_loc>
        </video:video>
    </url>
@endforeach
</urlset>
