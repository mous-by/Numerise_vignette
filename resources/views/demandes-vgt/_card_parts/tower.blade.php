{{-- Monument : image réelle téléversée par la mairie (`mairies.monument_path`), sinon dessin par défaut. Monument de l'Indépendance (Bamako) : dessin original d'après la photo fournie par le développeur — deux ailes inclinées, trois arches emboîtées, frise à motifs, flèche à dôme doré. --}}
@if ($monument = $demande->mairie->monumentUrl())
<img class="{{ $class ?? '' }}" src="{{ $monument }}" alt="Monument" style="object-fit:cover;border-radius:1mm">
@else
<svg class="{{ $class ?? '' }}" viewBox="0 0 100 150" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="tw-{{ $uid ?? 'a' }}" x1="0" x2="1">
      <stop offset="0" stop-color="{{ $c1 ?? '#cfc7b6' }}"/><stop offset=".5" stop-color="{{ $c2 ?? '#f1ebdd' }}"/><stop offset="1" stop-color="{{ $c1 ?? '#cfc7b6' }}"/>
    </linearGradient>
  </defs>
  {{-- corps : ailes latérales, épaules et flèche centrale --}}
  <path fill="url(#tw-{{ $uid ?? 'a' }})" stroke="{{ $c3 ?? '#8c8471' }}" stroke-width="1.2" stroke-linejoin="round"
        d="M3 148 L13 80 L20 68 L29 70 L32 44 Q33 31 40 29 L41 17 Q43 9 50 9 Q57 9 59 17 L60 29 Q67 31 68 44 L71 70 L80 66 L88 60 L97 148 Z"/>
  {{-- arches emboîtées (profondeur par teintes croissantes) --}}
  <path fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".22" d="M22 148 L22 102 Q22 66 50 66 Q78 66 78 102 L78 148 Z"/>
  <path fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".34" d="M32 148 L32 106 Q32 78 50 78 Q68 78 68 106 L68 148 Z"/>
  <path fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".5" d="M41 148 L41 112 Q41 90 50 90 Q59 90 59 112 L59 148 Z"/>
  <path fill="#1a1a1a" fill-opacity=".72" d="M45.5 148 L45.5 118 Q45.5 104 50 104 Q54.5 104 54.5 118 L54.5 148 Z"/>
  {{-- frise à motifs (balcon central) --}}
  <rect x="27" y="61" width="46" height="4" rx="1" fill="{{ $c3 ?? '#8c8471' }}"/>
  <g fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".85">
    @foreach (range(0, 8) as $i)
      <rect x="{{ 29 + $i * 4.7 }}" y="49" width="3" height="11" rx=".6"/>
      <rect x="{{ 28.2 + $i * 4.7 }}" y="52.5" width="4.6" height="2.2" rx=".5"/>
    @endforeach
  </g>
  {{-- ailes : balustrades --}}
  <rect x="9" y="79" width="14" height="2.6" transform="rotate(-14 9 79)" fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".7"/>
  <rect x="77" y="66" width="14" height="2.6" transform="rotate(-12 77 66)" fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".7"/>
  {{-- flèche : motifs empilés --}}
  <g fill="{{ $c3 ?? '#8c8471' }}" fill-opacity=".6">
    <rect x="44" y="26" width="4" height="2.6" transform="rotate(-25 44 26)"/><rect x="47" y="32" width="4" height="2.6" transform="rotate(-25 47 32)"/><rect x="44" y="38" width="4" height="2.6" transform="rotate(-25 44 38)"/>
  </g>
  {{-- dôme doré --}}
  <ellipse cx="50" cy="8" rx="5.2" ry="6.4" fill="#d9ab2f" stroke="#8a6a12" stroke-width="1"/>
  <path d="M48 3.5 Q50 -1 52 3.5Z" fill="#d9ab2f" stroke="#8a6a12" stroke-width=".8"/>
</svg>
@endif
