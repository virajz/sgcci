<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Badge{{ count($badges) > 1 ? 's' : ' — '.$badges[0]['name'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #fff; }
        .badge-page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .badge-page img { width: 100%; max-width: 700px; height: auto; display: block; }
        @media print {
            body { margin: 0; }
            .badge-page {
                width: 100%;
                min-height: unset;
                height: 100vh;
                page-break-after: always;
                page-break-inside: avoid;
            }
            .badge-page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    @foreach ($badges as $badge)
        <div class="badge-page">
            <img src="{{ $badge['url'] }}" alt="Badge — {{ $badge['name'] }}" />
        </div>
    @endforeach
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
