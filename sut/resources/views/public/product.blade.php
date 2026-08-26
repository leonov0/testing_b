@php
    $isFrench = $language === 'fr';
    $name = $isFrench ? $product->name_fr : $product->name_en;
    $description = $isFrench ? $product->description_fr : $product->description_en;
@endphp
<!doctype html>
<html lang="{{ $isFrench ? 'fr' : 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }}</title>
</head>
<body>
<nav aria-label="Language">
    <a href="?lang=en" lang="en" hreflang="en">English</a>
    <a href="?lang=fr" lang="fr" hreflang="fr">Français</a>
</nav>

<main>
    <h1>{!! $name !!}</h1>
    <p class="company-name">{{ $product->company?->company_name }}</p>

    @if ($product->image_path)
        <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $name }}">
    @else
        <img src="/images/product-placeholder.svg" alt="{{ $name }}">
    @endif

    <dl>
        <dt>{{ $isFrench ? 'Code GTIN' : 'GTIN' }}</dt>
        <dd class="gtin">{{ $product->gtin }}</dd>

        <dt>{{ $isFrench ? 'Poids brut' : 'Gross weight' }}</dt>
        <dd class="weight-gross">{{ $product->weight_gross }} {{ $product->weight_unit }}</dd>

        <dt>{{ $isFrench ? 'Contenu net' : 'Net content' }}</dt>
        <dd class="weight-net">{{ $product->weight_net }} {{ $product->weight_unit }}</dd>
    </dl>

    <p class="description" lang="{{ $isFrench ? 'fr' : 'en' }}">{{ $description }}</p>
</main>
</body>
</html>
