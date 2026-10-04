<?php

$mentions_legales_date                               = get_option( 'mlw_mentions_legales_date', wp_date("d/m/Y") );
$mentions_legales_date_formated                      = wp_date( 'd/m/Y', strtotime( $mentions_legales_date ) );
$is_society                                          = get_option( 'mlw_is_society', '' );
$society_name                                        = get_option( 'mlw_society_name', '' );
$society_social_form                                 = get_option( 'mlw_society_social_form', '' );
$society_social_capital                              = get_option( 'mlw_society_social_capital', '' );
$society_immatriculation_city                        = get_option( 'mlw_society_immatriculation_city', '' );
$society_siren                                       = get_option( 'mlw_society_siren', '' );
$society_address                                     = get_option( 'mlw_society_address', '' );
$society_phone_number                                = get_option( 'mlw_society_phone_number', '' );
$society_email                                       = get_option( 'mlw_society_email', '' );
$society_vat_number                                  = get_option( 'mlw_society_vat_number', '' );
$society_first_and_last_name_director_of_publication = get_option( 'mlw_society_first_and_last_name_director_of_publication', '' );
$personnal_first_and_last_name                       = get_option( 'mlw_personnal_first_and_last_name', '' );
$personnal_address                                   = get_option( 'mlw_personnal_address', '' );
$personnal_phone_number                              = get_option( 'mlw_personnal_phone_number', '' );
$personnal_email                                     = get_option( 'mlw_personnal_email', '' );
$host_name                                           = get_option( 'mlw_host_name', '' );
$host_address                                        = get_option( 'mlw_host_address', '' );
$host_phone_number                                   = get_option( 'mlw_host_phone_number', '' );
$data_collected                                      = get_option( 'mlw_data_collected', '' );
$data_collected_revoque_by_email                     = get_option( 'mlw_data_collected_revoque_by_email', '' );
$data_collected_revoque_by_poste                     = get_option( 'mlw_data_collected_revoque_by_poste', '' );
$data_collected_revoque_by_contact_form              = get_option( 'mlw_data_collected_revoque_by_contact_form', '' );
$data_collected_revoque_by_personnal_space           = get_option( 'mlw_data_collected_revoque_by_personnal_space', '' );

$site_link = '<a href="' . esc_url( get_site_url() ) . '">' . get_bloginfo( 'name' ) . '</a>';

?>

<h1>Mentions légales</h1>
<p>En vigueur au <time datetime="<?php echo esc_attr( $mentions_legales_date ); ?>"><?php echo esc_html( $mentions_legales_date_formated ); ?></time></p>

 
<p>Conformément aux dispositions des Articles 6-III et 19 de la Loi n°2004-575 du 21 juin 2004 pour la Confiance dans l’économie numérique, dite L.C.E.N., il est porté à la connaissance des utilisateurs et visiteurs, ci-après l'"Utilisateur", du site <?php echo wp_kses_post( $site_link, array() ); ?> , ci-après le "Site", les présentes mentions légales.</p>

<p>La connexion et la navigation sur le Site par l’Utilisateur implique acceptation intégrale et sans réserve des présentes mentions légales.</p>

<p id="mlw-credits-show-more" onclick="document.querySelector('#mlw-credits').style.height = 'auto'; this.style.display = 'none'">Voir les crédits.</p>
<div id="mlw-credits" style="height:0px; overflow:hidden;" >
    Ces mentions légales ont été générées via le plugin Mentions Légales par <a href="https://webdeclic.com/" target="_blank">Webdeclic SAS</a>.
    <p>
        <a href="#" onclick="document.querySelector('#mlw-credits').style.height = '0px'; document.querySelector('#mlw-credits-show-more').style.display = 'block'">Cacher les crédits</a>
    </p>
</div>

<h2>ARTICLE 1 - L'EDITEUR</h2>

<?php
if($is_society == 'on'){
    ?>
    <p>
        L'édition du Site est assurée par <?php echo esc_html( $society_name  . ' ' . $society_social_form ); ?> au capital de <?php echo esc_html( $society_social_capital ); ?> euros, immatriculée au Registre du Commerce et des Sociétés de <?php echo esc_html( $society_immatriculation_city ); ?> sous le numéro <?php echo esc_html( $society_siren ); ?> dont le siège social est situé au <?php echo esc_html( $society_address ); ?>.
    </p>
    <p>Numéro de téléphone : <?php echo esc_html( $society_phone_number ); ?>,</p>
    <p>Adresse e-mail : <?php echo esc_html( $society_email ); ?>.</p>
    <p>N° de TVA intracommunautaire : <?php echo esc_html( $society_vat_number ); ?></p>
    <p>Le Directeur de la publication est <?php echo esc_html( $society_first_and_last_name_director_of_publication ); ?> ci-après l'"Editeur".</p>
    <?php
} else {
    ?>
    <p>
        L'édition du Site est assurée par <?php echo esc_html( $personnal_first_and_last_name ); ?>, domicilié au <?php echo esc_html( $personnal_address ); ?>.
    </p>
    <p>Numéro de téléphone : <?php echo esc_html( $personnal_phone_number ); ?>,</p>
    <p>Adresse e-mail : <?php echo esc_html( $personnal_email ); ?>.</p>
    <?php
}
?>


<h2>ARTICLE 2 - L'HEBERGEUR</h2>

<p>L'hébergeur du Site est la société <?php echo esc_html( $host_name ); ?>, dont le siège social est situé au <?php echo esc_html( $host_address ); ?> , avec le numéro de téléphone : <?php echo esc_html( $host_phone_number ); ?>.</p>

<h2>ARTICLE 3 - ACCES AU SITE</h2>

<p>Le Site est accessible en tout endroit, 7j/7, 24h/24 sauf cas de force majeure, interruption programmée ou non et pouvant découlant d’une nécessité de maintenance.</p>

<p>En cas de modification, interruption ou suspension du Site, l'Editeur ne saurait être tenu responsable.</p>

<h2>ARTICLE 4 - COLLECTE DES DONNEES</h2>
<?php
if( $data_collected == 'on' ) {
    ?>
    <p>Le Site assure à l'Utilisateur une collecte et un traitement d'informations personnelles dans le respect de la vie privée conformément à la loi n°78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés. </p>

    <p>En vertu de la loi Informatique et Libertés, en date du 6 janvier 1978, l'Utilisateur dispose d'un droit d'accès, de rectification, de suppression et d'opposition de ses données personnelles. L'Utilisateur exerce ce droit :</p>
    <ul>
        <?php if( $data_collected_revoque_by_email !== '') { ?> <li>par mail à l'adresse email <?php echo esc_html( $data_collected_revoque_by_email ); ?></li><?php } ?>
        <?php if( $data_collected_revoque_by_poste !== '') { ?> <li>par courrier postal à l'adresse <?php echo esc_html( $data_collected_revoque_by_poste ); ?></li><?php } ?>
        <?php if( $data_collected_revoque_by_contact_form !== '') { ?> <li>via le <a href="<?php echo esc_html( $data_collected_revoque_by_contact_form ); ?>" >formulaire de contact</a></li><?php } ?>
        <?php if( $data_collected_revoque_by_personnal_space !== '') { ?> <li>via l'<a href="<?php echo esc_html( $data_collected_revoque_by_personnal_space ); ?>" >espace personnel</a></li><?php } ?>
    </ul>
    <?php
} else {
    ?>
    <p>Le Site ne collecte aucune donnée personnelle.</p>
    <?php
}
?> 
<p>Toute utilisation, reproduction, diffusion, commercialisation, modification de toute ou partie du Site, sans autorisation de l’Editeur est prohibée et pourra entraînée des actions et poursuites judiciaires telles que notamment prévues par le Code de la propriété intellectuelle et le Code civil.</p>

<p>Pour plus d’informations, se reporter aux CGU du site <?php echo wp_kses_post( $site_link, array() ); ?> accessible à la rubrique "CGU"</p>
<p>Pour plus d’informations, se reporter aux CGV du site <?php echo wp_kses_post( $site_link, array() ); ?> accessible à la rubrique "CGV" </p>
<p>Pour plus d'informations en matière de protection des données à caractère personnel , se reporter à la Charte en matière de protection des données à caractère personnel du site <?php echo wp_kses_post( $site_link, array() ); ?> accessible à la rubrique "Données personnelles"</p>
<p>Pour plus d'informations en matière de cookies, se reporter à la Charte en matière de cookies du site <?php echo wp_kses_post( $site_link, array() ); ?> accessible à la rubrique "Cookies"</p>
