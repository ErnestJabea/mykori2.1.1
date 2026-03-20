@extends('front-end/app/app-home', ['Produits disponibles', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden products-page'])

@section('content')
    <main class="main-content has-sidebar">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
            <h2 class="h2">Profile</h2>
        </div>


        <div class="flex flex-col gap-4 xxl:gap-6">
            <!-- Change Password -->
            <div class="box xl:p-8 xxl:p-10">
                <div id="success_message"></div>
                <form class="mt-6 xl:mt-8 grid grid-cols-2 gap-4 xxxl:gap-6" action="{{ route('change-password') }}"
                    method="post" id="changePasswordForm">
                    {{ csrf_field() }}
                    <div class="col-span-2 ">
                        <label for="email" class="md:text-lg font-medium block mb-4">
                            Ancien mot de passse
                        </label>
                        <div
                            class="bg-primary/5 dark:bg-bg3 border border-n30 dark:border-n500 rounded-3xl px-3 md:px-6 py-2 md:py-3 relative">
                            <input type="password" class="w-11/12 text-sm bg-transparent p-0 border-none"
                                placeholder="Ancien mot de passe" id="current_password" name="current_password" required />
                            <span class="absolute eye-icon ltr:right-5 rtl:left-5 top-1/2 -translate-y-1/2 cursor-pointer"
                                id="password1-btn">
                            </span>
                        </div>
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="email" class="md:text-lg font-medium block mb-4">
                            Nouveau mot de passe
                        </label>
                        <div class="bg-primary/5 dark:bg-bg3 border border-n30 dark:border-n500 rounded-3xl px-3 md:px-6 py-2 md:py-3 relative">
                            <input type="password" class="w-11/12 text-sm bg-transparent p-0 border-none"
                                placeholder="Nouveau mot de passe" id="new_password" name="new_password" required />
                            <span class="absolute eye-icon ltr:right-5 rtl:left-5 top-1/2 -translate-y-1/2 cursor-pointer"
                                id="password2-btn">
                            </span>
                        </div>
                        <div class="error" id="new_password_error"></div>
                    </div>
                    <div class="col-span-2  md:col-span-1">
                        <label for="email" class="md:text-lg font-medium block mb-4">
                            Confirmez le mot de passe
                        </label>
                        <div class="bg-primary/5 dark:bg-bg3 border border-n30 dark:border-n500 rounded-3xl px-3 md:px-6 py-2 md:py-3 relative">
                            <input type="password" class="w-11/12 text-sm bg-transparent p-0 border-none"
                                placeholder="Confirmez le mot de passe" id="new_password_confirmation_" name="new_password_confirmation"
                                required  />
                            <span class="absolute eye-icon ltr:right-5 rtl:left-5 top-1/2 -translate-y-1/2 cursor-pointer"
                                id="password3-btn">
                            </span>
                        </div>
                        <div class="error" id="new_password_confirmation_error"></div>

                    </div>
                    <div class="col-span-2 flex gap-4">
                        <button type="submit" class="btn px-5">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </main>



@endsection

@section('script_front_end')
<script>
    $(document).ready(function() {
        $('#changePasswordForm').on('submit', function(event) {
            event.preventDefault();

            // Effacer les messages d'erreur précédents
            $('.error').text('');
            $('#success_message').text('');

            // Récupérer les valeurs des champs de mot de passe
            var newPassword = $('#new_password').val().trim();
            var newPasswordConfirmation = $('#new_password_confirmation_').val().trim();

            // Vérifier que les mots de passe correspondent
            if (newPassword !== newPasswordConfirmation) {
                $('#new_password_confirmation_error').text('Les mots de passe ne correspondent pas');
                return;
            }


            // Vérifier que le nouveau mot de passe respecte les exigences de format
            var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(newPassword)) {
                $('#new_password_error').text(
                    'Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial.'
                );
                return;
            }

            // Envoyer la requête AJAX si toutes les validations passent
            $.ajax({
                url: '{{ route('change-password') }}',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        $('#success_message').text("Votre mot de passe a été mis à jour avec succès");
                        $('#changePasswordForm')[0].reset();
                    }
                },
                error: function(response) {
                    if (response.status === 422) {
                        var errors = response.responseJSON.errors;
                        for (var key in errors) {
                            $('#' + key + '_error').text(errors[key][0]);
                        }
                    }
                }
            });
        });
    });

</script>
@endsection
