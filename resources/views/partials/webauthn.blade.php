{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    Passkeys in the browser. A button with data-a2f-passkey="get|create",
    data-options and data-verify URLs, inside a form that carries the CSRF
    token: the script asks the server for options, lets the browser do the
    ceremony and posts the answer. Messages go to [data-a2f-message].
--}}
<script @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif>
(function () {
    var unsupported = @json(__('atrium-2fa::two-factor.passkey.unsupported'));
    var cancelled = @json(__('atrium-2fa::two-factor.passkey.cancelled'));

    function toBytes(value) {
        var text = atob(value.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((value.length + 3) % 4));
        var bytes = new Uint8Array(text.length);
        for (var i = 0; i < text.length; i++) bytes[i] = text.charCodeAt(i);
        return bytes.buffer;
    }

    function toText(buffer) {
        var bytes = new Uint8Array(buffer), text = '';
        for (var i = 0; i < bytes.length; i++) text += String.fromCharCode(bytes[i]);
        return btoa(text).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function post(url, form, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value
            },
            body: JSON.stringify(body || {})
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) throw new Error(data.message || response.statusText);
                return data;
            });
        });
    }

    function extra(form) {
        var data = {};
        new FormData(form).forEach(function (value, key) { if (key !== '_token') data[key] = value; });
        return data;
    }

    document.querySelectorAll('[data-a2f-passkey]').forEach(function (button) {
        var form = button.closest('form');
        var message = form.querySelector('[data-a2f-message]');

        function say(text) {
            if (message) { message.textContent = text; message.hidden = !text; }
        }

        if (!window.PublicKeyCredential) {
            button.disabled = true;
            say(unsupported);
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            say('');
            button.disabled = true;
            var mode = button.getAttribute('data-a2f-passkey');

            post(button.getAttribute('data-options'), form).then(function (options) {
                var key = options.publicKey;
                key.challenge = toBytes(key.challenge);
                if (key.user) key.user.id = toBytes(key.user.id);
                (key.allowCredentials || key.excludeCredentials || []).forEach(function (item) { item.id = toBytes(item.id); });

                return mode === 'create' ? navigator.credentials.create({ publicKey: key }) : navigator.credentials.get({ publicKey: key });
            }).then(function (credential) {
                var response = credential.response;
                var body = extra(form);
                body.id = credential.id;
                body.rawId = toText(credential.rawId);
                body.type = credential.type;
                body.response = { clientDataJSON: toText(response.clientDataJSON) };
                if (mode === 'create') {
                    body.response.attestationObject = toText(response.attestationObject);
                } else {
                    body.response.authenticatorData = toText(response.authenticatorData);
                    body.response.signature = toText(response.signature);
                    if (response.userHandle) body.response.userHandle = toText(response.userHandle);
                }
                return post(button.getAttribute('data-verify'), form, body);
            }).then(function (data) {
                window.location.assign(data.redirect || window.location.href);
            }).catch(function (error) {
                button.disabled = false;
                say(error && error.name === 'NotAllowedError' ? cancelled : (error && error.message) || cancelled);
            });
        });
    });
})();
</script>
