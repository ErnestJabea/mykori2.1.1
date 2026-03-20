<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <div style="font-family: Helvetica,Arial,sans-serif;min-width:1000px;overflow:auto;line-height:2">
        <div style="margin:50px auto;width:70%;padding:20px 0">
            <div style="border-bottom:1px solid #eee">
                <a href="#!" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600"><img
                        src="https://koriassetmanagement.com//storage/logos/July2023/FMsK7cKiP27MVqYl63rU.png"
                        width="100px" alt=""></a>
            </div>
            <p style="font-size:1.1em">Bonjour, M./Mme <strong>{{ $nom_client }}</strong></p>
            <p>Par la présente, nous souhaitons vous notifier le status de votre transaction initiée le
                <strong>{{ $date_transaction }}</strong>. Les détails ci-dessous :
            </p>

            <p>Produit : <strong>{{ $nom_produit }}</strong></p>
            <p>Taux d'intérêt à l'initiation de la transaction : <strong>{{ $vl }}</strong></p>
            <p>Référence de transaction: <strong>{{ $ref_transaction }}</strong></p>
            <p>Gains potentiels (par an): <strong>XAF {{ $nbpart }}</strong></p>
            <p>Montant de la transaction: <strong> XAF {{ number_format($montantTransaction, 0, ' ', ' ') }}</strong>
            <p>Status de la transaction: <strong> {{ $status_ }}</strong>
            </p>

            <hr style="border:none;border-top:1px solid #eee" />

        </div>
    </div>
</body>

</html>
