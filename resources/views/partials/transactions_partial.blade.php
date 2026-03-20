@foreach ($transactions as $transaction)
    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
        <!-- Afficher les détails de la transaction -->
    </tr>
@endforeach

{{ $transactions->links('pagination::bootstrap-4') }}
