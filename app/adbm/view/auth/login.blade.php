@extends('auth.authbase' , ['title'=>'Sign In'])

@section('form')
    <div class="form-panel">
        @component('snip.input', [
            'label'=>['username', 'account'],
            'id'=>'username',
            'autocomplete'=>'username webauthn',
            'placeholder'=>'Account Name',
            'value'=>$username ?? '',
            'autofocus'=>true
        ])
        @endcomponent

        <div class="my-2">
            <button type="submit"
                    name="_login"
                    id="_login_continue"
                    value="li"
                    class="bar-button button-hover">
                Continue
            </button>
        </div>
    </div>

    <div id="passkey-login" hidden>
        <div class="flex items-center gap-2 my-2">
            <hr class="flex-1"/>
            <span class="text-sm">or</span>
            <hr class="flex-1"/>
        </div>

        <div class="form-panel">
            <button type="button"
                    id="_login_passkey"
                    class="bar-button cyan button-hover">
                <div class="flex items-center space-x-2 text-white">
                    <span>Use Security Key</span>
                    <div class="w-6 h-6 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 10V12H9V14H7V12H5.8C5.4 13.2 4.3 14 3 14C1.3 14 0 12.7 0 11S1.3 8 3 8C4.3 8 5.4 8.8 5.8 10H11M3 10C2.4 10 2 10.4 2 11S2.4 12 3 12 4 11.6 4 11 3.6 10 3 10M16 14C18.7 14 24 15.3 24 18V20H8V18C8 15.3 13.3 14 16 14M16 12C13.8 12 12 10.2 12 8S13.8 4 16 4 20 5.8 20 8 18.2 12 16 12Z" /></svg>
                    </div>
                </div>
            </button>
        </div>
    </div>

    <div>
        <a href="/" class="text-sm underline">Cancel</a>
    </div>
@endsection

@section('bscript')
    <script>
        // async function startPasskeyLogin() {
        //     const options = await fetch('/api/v1/login/passkey_options');
        //     const credential = await navigator.credentials.get({ publicKey: options });
        //     await fetch('/api/v1/login/passkey_verify', { credential });
        // }
        //
        // async function startConditionalPasskeyLogin() {
        //     const optionsResponse = await fetch('/api/login/v1/passkey/options', {
        //         method: 'POST',
        //         headers: {
        //             'Content-Type': 'application/json',
        //         },
        //         body: JSON.stringify({
        //             conditional: true
        //         })
        //     });
        //
        //     const optionsPacket = await optionsResponse.json();
        //     const publicKey = wdPreparePublicKeyRequestOptions(optionsPacket.publicKey);
        //
        //     const credential = await navigator.credentials.get({
        //         publicKey,
        //         mediation: 'conditional'
        //     });
        //
        //     if (!credential)
        //         return;
        //
        //     await fetch('/api/v1/login/passkey_verify', { credential });
        // }
        // document.addEventListener('DOMContentLoaded', async () => {
        //     const canUseWebAuthn = await wdCanUseWebAuthn();
        //     const canUseConditionalUi = await wdCanUseConditionalPasskeyUi();
        //
        //     const passkeyBlock = document.getElementById('passkey-login');
        //     const passkeyButton = document.getElementById('_login_passkey');
        //
        //     if (passkeyBlock) {
        //         passkeyBlock.hidden = !canUseWebAuthn;
        //     }
        //
        //     if (passkeyButton)
        //         passkeyButton.addEventListener('click', startPasskeyLogin ) ;
        //
        //     if (canUseWebAuthn && canUseConditionalUi) {
        //         // startConditionalPasskeyLogin();
        //     }
        // });
    </script>
@endsection