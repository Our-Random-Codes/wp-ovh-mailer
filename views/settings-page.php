<?php
/**
 * Settings page view for OVH Mailer.
 *
 * Available variables:
 *   $options            array   Plugin options (login, password, port, enabled)
 *   $current_port       int     Currently selected port
 *   $is_configured      bool    Whether SMTP is fully configured
 *   $port_choices       array   [ port_value => label ]
 *   $default_test_email string  Current admin user e-mail
 *   $option_name        string  Option name used for form field names
 *   $plugin_url         string  Plugin base URL (trailing slash)
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1 style="
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.8rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        background: linear-gradient(90deg, #000E9C, #3a56f0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1rem;
    ">
        <img
            src="<?php echo esc_url( $plugin_url . 'assets/images/ovh-icon.svg' ); ?>"
            alt="OVH"
            style="height:1.8rem; flex-shrink:0; filter: drop-shadow(0 2px 4px rgba(0,14,156,.25));"
        >OVH Mailer
    </h1>
    <form
            id="ovh-mailer"
            method="post"
            action="options.php"
    >
        <?php settings_fields( 'ovh_mailer' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="ovh-mailer-login">
                        Login
                    </label>
                </th>
                <td>
                    <input
                            type="email"
                            id="ovh-mailer-login"
                            name="<?php echo esc_attr( $option_name ); ?>[login]"
                            value="<?php echo esc_attr( $options['login'] ); ?>"
                            class="regular-text"
                            autocomplete="email"
                    >
                    <p class="description">
                        Votre adresse e-mail OVH.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ovh-mailer-password">
                        Mot de passe
                    </label>
                </th>
                <td>
                    <input
                            type="password"
                            id="ovh-mailer-password"
                            name="<?php echo esc_attr( $option_name ); ?>[password]"
                            value="<?php echo esc_attr( $options['password'] ); ?>"
                            class="regular-text"
                            autocomplete="current-password"
                    >
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Protocole / Port', 'ovh-mailer' ); ?>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <?php esc_html_e( 'Protocole / Port', 'ovh-mailer' ); ?>
                        </legend>
                        <?php foreach ( $port_choices as $port_value => $port_label ) : ?>
                            <label style="display:block; margin-bottom:6px;">
                                <input
                                        type="radio"
                                        name="<?php echo esc_attr( $option_name ); ?>[port]"
                                        value="<?php echo esc_attr( $port_value ); ?>"
                                        <?php checked( $current_port, $port_value ); ?>
                                >
                                <?php echo esc_html( $port_label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Statut
                </th>
                <td>
                    <label>
                        <input
                                type="checkbox"
                                name="<?php echo esc_attr( $option_name ); ?>[enabled]"
                                value="1"
                                <?php checked( $options['enabled'], 1 ); ?>
                        >
                        <?php esc_html_e( 'Activer l\'envoi via OVH', 'ovh-mailer' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Enregistrer' ); ?>
    </form>

    <?php if ( $is_configured ) : ?>
        <div id="ovh-mailer-test-section" style="margin-top: 1.5em; max-width: 500px;">
            <h2><?php esc_html_e( 'Envoyer un e-mail de test', 'ovh-mailer' ); ?></h2>
            <label style="display:inline-flex; align-items:center; gap:6px; margin-bottom:10px; font-weight:600;">
                <input type="checkbox" id="ovh-mailer-debug-toggle">
                <?php esc_html_e( 'Afficher le journal SMTP', 'ovh-mailer' ); ?>
            </label>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input
                        type="email"
                        id="ovh-mailer-test-email"
                        value="<?php echo esc_attr( $default_test_email ); ?>"
                        class="regular-text"
                        placeholder="destinataire@exemple.com"
                        style="flex:1;"
                        autocomplete="email"
                >
                <button
                        type="button"
                        id="ovh-mailer-test-btn"
                        class="button button-secondary"
                >
                    <?php esc_html_e( 'Envoyer le test', 'ovh-mailer' ); ?>
                </button>
            </div>
            <p id="ovh-mailer-test-result" style="margin-top:8px; font-weight:600;"></p>
            <pre
                    id="ovh-mailer-smtp-log"
                    style="display:none; margin-top:8px; padding:12px; background:#1d2327; color:#a8c7e8;
                           font-size:12px; line-height:1.6; white-space:pre-wrap; word-break:break-all;
                           border-radius:4px; max-height:400px; overflow-y:auto;"
            ></pre>
        </div>
        <script>
            (function () {
                var btn         = document.getElementById('ovh-mailer-test-btn');
                var input       = document.getElementById('ovh-mailer-test-email');
                var result      = document.getElementById('ovh-mailer-test-result');
                var logBox      = document.getElementById('ovh-mailer-smtp-log');
                var debugToggle = document.getElementById('ovh-mailer-debug-toggle');

                debugToggle.addEventListener('change', function () {
                    if (logBox.textContent) {
                        logBox.style.display = this.checked ? 'block' : 'none';
                    }
                });

                btn.addEventListener('click', function () {
                    result.textContent = '<?php echo esc_js( __( 'Envoi en cours…', 'ovh-mailer' ) ); ?>';
                    result.style.color = '#666';
                    logBox.style.display = 'none';
                    logBox.textContent  = '';
                    btn.disabled = true;

                    var data = new FormData();
                    data.append('action', 'ovh_mailer_test');
                    data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'ovh_mailer_test_nonce' ) ); ?>');
                    data.append('to', input.value);

                    fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        body: data,
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            var d = json.data || {};
                            if (d.sent) {
                                result.textContent = '✔ <?php echo esc_js( __( 'Accepté par SMTP. Vérifiez votre boîte.', 'ovh-mailer' ) ); ?>';
                                result.style.color = 'green';
                            } else {
                                result.textContent = '✖ ' + (d.error || '<?php echo esc_js( __( 'Échec d\'envoi.', 'ovh-mailer' ) ); ?>');
                                result.style.color = 'red';
                            }
                            if (d.smtp_log) {
                                logBox.textContent    = d.smtp_log;
                                logBox.style.display  = debugToggle.checked ? 'block' : 'none';
                            }
                        })
                        .catch(function () {
                            result.textContent = '<?php echo esc_js( __( 'Erreur réseau.', 'ovh-mailer' ) ); ?>';
                            result.style.color = 'red';
                        })
                        .finally(function () {
                            btn.disabled = false;
                        });
                });
            })();
        </script>
    <?php endif; ?>
</div>
