<?php
/**
 * Admin notice view for OVH Mailer.
 *
 * Available variables:
 *   $url  string  URL to the OVH Mailer settings page
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice-warning">
    <p>
        <strong>OVH Mailer :</strong>
        Le serveur SMTP OVH n'est pas configuré.
        <a href="<?php echo esc_url( $url ); ?>">
            Configurer OVH Mailer
        </a>
    </p>
</div>
