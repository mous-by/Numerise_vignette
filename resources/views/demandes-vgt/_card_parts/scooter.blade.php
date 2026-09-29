{{-- Pictogramme moto : silhouette plate d'une vraie vignette (masque coloré selon le modèle) ou, avec `photo`, l'image « photo » de la vignette 2018. --}}
@if (! empty($photo))
<img class="{{ $class ?? '' }}" src="{{ asset('assets/images/vignette/scooter-photo.png') }}" alt="" style="object-fit:contain">
@else
<span class="{{ $class ?? '' }}" style="background:{{ $color ?? '#222' }};-webkit-mask:url('{{ asset('assets/images/vignette/scooter-flat.png') }}') center/contain no-repeat;mask:url('{{ asset('assets/images/vignette/scooter-flat.png') }}') center/contain no-repeat"></span>
@endif
