<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    @php

        date_default_timezone_set('Africa/Douala');
        setlocale(LC_TIME, 'fr_FR.UTF-8');
    @endphp
    <div style="font-family: Helvetica,Arial,sans-serif;min-width:1000px;overflow:auto;line-height:2">
        <div style="margin:50px auto;width:70%;padding:20px 0">
            <div style="border-bottom:1px solid #eee">
                <a href="#!" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600"><img
                        src="https://koriassetmanagement.com//storage/logos/July2023/FMsK7cKiP27MVqYl63rU.png"
                        width="100px" alt=""></a>
            </div>
            <p style="font-size:1.1em">Bonjour, <strong></strong></p>
            <p>Vous avez un client intéressé par le produit <strong>{{ $product_name->title }}</strong>. Les détails
                ci-dessous
                : </p>

            <p>Nom : <strong>{{ $username }}</strong></p>
            <p>Email : <strong>{{ $useremail }}</strong></p>
            <p>Valeur Liquidative à la souscription : <strong>XAF {{ number_format($vl_actuel, 2, ',', ' ') }}</strong></p>
            <p>Nombre de part : <strong>{{ round($valeurLiquidative, 2) }}</strong></p>
            <p>Montant total à payer : <strong>{{ number_format($montantTotal, 0, ' ', ' ') }} (incluant les frais de
                    souscription à hauteur de {{ $product_name->free }}%)</strong></p>
            <p>Date d'enregistrement: <strong>@php  echo strftime("%A %d %B %Y, %H:%M:%S") ;  @endphp</strong></p>

            <hr style="border:none;border-top:1px solid #eee" />

        </div>
    </div>
</body>

</html>
