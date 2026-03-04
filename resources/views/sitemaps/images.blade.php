{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        <image:image>
            <image:loc>{{ $entry['image_loc'] }}</image:loc>
            <image:title>{{ htmlspecialchars($entry['image_title'], ENT_XML1) }}</image:title>
            <image:caption>{{ htmlspecialchars($entry['image_caption'], ENT_XML1) }}</image:caption>
        </image:image>
    </url>
@endforeach
</urlset>
