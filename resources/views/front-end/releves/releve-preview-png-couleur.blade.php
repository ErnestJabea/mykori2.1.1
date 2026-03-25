@php
    use Carbon\Carbon;
    Carbon::setLocale('fr');
    $logoPath = public_path('images/logo-with-text.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

    $total_placement = 0;
    $total_gain_mois = 0;
    $total_gains = 0;
    foreach ($produits as $p) {
        $total_placement += $p->capital;
        $total_gain_mois += $p->gain_mensuel;
        $total_gains += $p->gain_total;
    }
@endphp
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Relevé de Compte – {{ $client->name }}</title>
    <style>
        /* ── Reset ── */
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ── Base ── */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.5;
        }

        .page {
            max-width: 794px;
            margin: 0 auto;
            padding: 40px 44px 36px;
            background: #fff;
        }

        /* ── Header ── */
        .hd {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid #531d09;
        }

        .hd-logo img {
            width: 130px;
            display: block;
        }

        .hd-logo-txt {
            font-size: 17px;
            font-weight: 700;
            color: #531d09;
        }

        .hd-client {
            text-align: right;
            font-size: 11px;
            line-height: 1.7;
        }

        .hd-client-name {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        /* ── Title band ── */
        .title-band {
            background: #531d09;
            color: #fff;
            text-align: center;
            padding: 14px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
        }

        .title-band .doc-type {
            font-size: 10px;
            letter-spacing: .1em;
            text-transform: uppercase;
            opacity: .75;
        }

        .title-band .doc-name {
            font-size: 19px;
            font-weight: 700;
            margin: 4px 0 2px;
        }

        .title-band .doc-period {
            font-size: 12px;
            opacity: .85;
        }

        /* ── Summary pills ── */
        .summary-row {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .pill {
            flex: 1;
            border: 1px solid #e8e0d0;
            border-radius: 6px;
            padding: 12px 14px;
            background: #fffdf7;
        }

        .pill-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #9c8c6e;
            margin-bottom: 5px;
        }

        .pill-value {
            font-size: 15px;
            font-weight: 700;
            color: #531d09;
        }

        .pill-sub {
            font-size: 10px;
            color: #9c8c6e;
            margin-top: 1px;
        }

        .pill.accent {
            background: #531d09;
            border-color: #531d09;
        }

        .pill.accent .pill-label {
            color: rgba(255, 255, 255, .6);
        }

        .pill.accent .pill-value {
            color: #ebb008;
            font-size: 17px;
        }

        .pill.accent .pill-sub {
            color: rgba(255, 255, 255, .55);
        }

        /* ── Section label ── */
        .section-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #531d09;
            margin-bottom: 8px;
            padding-left: 2px;
        }

        /* ── Table shared ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th {
            background: #531d09;
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            letter-spacing: .04em;
        }

        th.center,
        td.center {
            text-align: center;
        }

        th.right,
        td.right {
            text-align: right;
        }

        td {
            padding: 9px 10px;
            border-bottom: 1px solid #f0ebe2;
            color: #2a2a2a;
        }

        tbody tr:hover td {
            background: #fffdf7;
        }

        /* ── Table 1 – placements ── */
        .t1 {
            margin-bottom: 20px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e8e0d0;
        }

        /* ── Table 2 – rendement ── */
        .t2 {
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e8e0d0;
            margin-bottom: 20px;
        }

        .t2 .sub-head th {
            background: #ebb008;
            color: #1a1a1a;
            font-size: 10px;
        }

        .t2 .total-row td {
            background: #fff8e8;
            font-weight: 700;
            color: #531d09;
            font-size: 11.5px;
            border-top: 2px solid #ebb008;
            border-bottom: 2px solid #ebb008;
        }

        .t2 .gain-val {
            color: #2e7d32;
            font-weight: 600;
        }

        .t2 .perte-val {
            color: #c62828;
            font-weight: 600;
        }

        .t2 .vide {
            background: #f9f9f9;
        }

        .t2 .new-tag {
            font-size: 9px;
            color: #ff8c00;
            font-style: italic;
        }

        /* ── Disclaimer ── */
        .disclaimer {
            font-size: 9.5px;
            color: #6b6b6b;
            line-height: 1.6;
            padding: 12px 14px;
            background: #f9f7f3;
            border-left: 3px solid #ebb008;
            border-radius: 0 4px 4px 0;
            margin-bottom: 24px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e0d9cf;
            padding-top: 14px;
            text-align: center;
            font-size: 9px;
            color: #9c8c6e;
            line-height: 1.8;
        }

        .footer strong {
            color: #531d09;
        }

        /* ── Print ── */
        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 20px 24px;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ── En-tête ── --}}
        <div class="hd">
            <div class="hd-logo">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="KORI Asset Management">
                @else
                    <span class="hd-logo-txt">KORI Asset Management</span>
                @endif
            </div>
            <div class="hd-client">
                <div class="hd-client-name">
                    @if ($client->genre == 0)
                        M.
                    @elseif($client->genre == 1)
                        Mme
                    @endif
                    {{ $client->name }}
                </div>
                <div>{{ $client->localisation }}</div>
                <div>{{ $client->bp }}</div>
                <div>{{ $client->email }}</div>
            </div>
        </div>

        {{-- ── Bandeau titre ── --}}
        <div class="title-band">
            <div class="doc-type">Gestion Privée — Document confidentiel</div>
            <div class="doc-name">Relevé de compte mensuel</div>
            <div class="doc-period">{{ ucfirst($periode) }}</div>
        </div>

        {{-- ── Pills de synthèse ── --}}
        <div class="summary-row">
            <div class="pill">
                <div class="pill-label">Total placé</div>
                <div class="pill-value">{{ number_format($total_placement, 0, ',', ' ') }}</div>
                <div class="pill-sub">XAF</div>
            </div>
            <div class="pill">
                <div class="pill-label">Gains cumulés</div>
                <div class="pill-value">{{ number_format($total_gains, 0, ',', ' ') }}</div>
                <div class="pill-sub">XAF</div>
            </div>
            <div class="pill">
                <div class="pill-label">Gain du mois</div>
                <div class="pill-value">{{ number_format($total_gain_mois, 0, ',', ' ') }}</div>
                <div class="pill-sub">XAF</div>
            </div>
            <div class="pill accent">
                <div class="pill-label">Valorisation au {{ $date_releve }}</div>
                <div class="pill-value">{{ number_format($valorisation_courante, 0, ',', ' ') }}</div>
                <div class="pill-sub">XAF</div>
            </div>
        </div>

        {{-- ── Table 1 : liste des placements ── --}}
        <div class="section-label">Détail des placements</div>
        <div class="t1">
            <table>
                <thead>
                    <tr>
                        <th>Placement</th>
                        <th>Date de valeur</th>
                        <th>Date d'échéance</th>
                        <th class="right">Montant (XAF)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produits as $p)
                        <tr>
                            <td>{{ $p->nom }}</td>
                            <td>{{ Carbon::createFromFormat('d/m/Y', $p->souscription)->format('d/m/Y') }}</td>
                            <td>{{ Carbon::createFromFormat('d/m/Y', $p->date_echeance)->format('d/m/Y') }}</td>
                            <td class="right">{{ number_format($p->capital, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Table 2 : rendement mensuel ── --}}
        <div class="section-label">Rendement mensuel du portefeuille</div>
        <div class="t2">
            <table>
                <thead>
                    <tr>
                        <th colspan="4">
                            Total placements : XAF {{ number_format($total_placement, 0, ',', ' ') }}
                            &nbsp;|&nbsp; Total gains : XAF {{ number_format($total_gains, 0, ',', ' ') }}
                        </th>
                        <th colspan="2" class="center">Rendement mensuel (XAF)</th>
                        <th class="center">Valeur totale</th>
                    </tr>
                    <tr class="sub-head">
                        <th>Intitulé</th>
                        <th>Placement</th>
                        <th class="center">Montant</th>
                        <th class="center">Taux net/an</th>
                        <th class="center">Gains</th>
                        <th class="center">Pertes</th>
                        <th class="center vide"></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Ligne valorisation précédente --}}
                    <tr class="total-row">
                        <td colspan="4">Valorisation au {{ $date_releve_precedent }}</td>
                        <td></td>
                        <td></td>
                        <td class="right">{{ number_format($valorisation_precedente, 0, ',', ' ') }}</td>
                    </tr>

                    @foreach ($produits as $p)
                        <tr>
                            <td>
                                Mandat de Gestion
                                @if (isset($p->produit_jeune) && $p->produit_jeune == 1)
                                    <br><span class="new-tag">Nouveau placement</span>
                                @endif
                            </td>
                            <td>{{ $p->nom }}</td>
                            <td class="center">{{ number_format($p->capital, 0, ',', ' ') }}</td>
                            <td class="center">{{ $p->taux }} %</td>
                            <td class="right gain-val">+ {{ number_format($p->gain_mensuel, 0, ',', ' ') }}</td>
                            <td class="right perte-val">—</td>
                            <td class="right vide"></td>
                        </tr>
                    @endforeach

                    {{-- Ligne valorisation courante --}}
                    <tr class="total-row">
                        <td colspan="4">Valorisation au {{ $date_releve }}</td>
                        <td></td>
                        <td></td>
                        <td class="right">{{ number_format($valorisation_courante, 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ── Disclaimer ── --}}
        <div class="disclaimer">
            Sauf réclamation de votre part dans un délai de <strong>30 jours</strong> à compter de sa date d'envoi,
            nous considérons ce relevé comme approuvé. &nbsp;|&nbsp;
            <strong>MG</strong> : Mandat de Gestion
        </div>

        {{-- ── Pied de page ── --}}
        <div class="footer">
            <strong>KORI Asset Management</strong> — Rue 1.131 DIKOUME BELL, BP : 1245 BALI-DOUALA<br>
            info@koriassetmanagement.com &nbsp;|&nbsp; www.koriassetmanagement.com
        </div>

    </div>
</body>

</html>
