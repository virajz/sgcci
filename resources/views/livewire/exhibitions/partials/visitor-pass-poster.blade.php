{{--
    Visitor pass poster.
    Creative image: 1080 × 1920 px
    Blue box inner fill: x 295–782, y 619–1105
      left   = 295/1080  = 27.31%
      right  = (1080-782)/1080 = 27.59%
      top    = 619/1920  = 32.24%
      bottom = (1920-1105)/1920 = 42.45%
    Name baseline y=1390 → top = 1390/1920 = 72.4%
--}}
<div class="flex flex-col items-center gap-4">
    <div class="relative w-full max-w-sm mx-auto overflow-hidden rounded-2xl shadow-xl">

        {{-- Creative background --}}
        <img src="{{ asset('creative.jpeg') }}" alt="Visitor Pass" class="block w-full h-auto">

        {{-- QR overlay — sits exactly inside the blue box fill area.
             The SVG uses the same blue background colour so it blends in.
             We apply p-[6%] padding so the QR doesn't touch the box edges. --}}
        <div class="absolute flex items-center justify-center p-[5%]"
             style="left:27.31%; right:27.59%; top:32.24%; bottom:42.45%;">
            <div class="w-full h-full [&>svg]:w-full [&>svg]:h-full">
                {!! $qrCodeSvg !!}
            </div>
        </div>

        {{-- Visitor name --}}
        <div class="absolute inset-x-0 flex items-center justify-center px-[8%]"
             style="top:72.4%;">
            <span style="color:#39318a;font-family:sans-serif;font-weight:900;
                         font-size:clamp(0.45rem,2.2vw,0.8rem);
                         text-transform:uppercase;letter-spacing:0.08em;
                         text-align:center;line-height:1;">
                {{ $visitorName }}
            </span>
        </div>

    </div>

    <flux:button variant="primary" href="{{ $downloadUrl }}" icon="arrow-down-tray" class="w-full max-w-sm">
        Download Pass — {{ $visitorName }}
    </flux:button>
</div>
