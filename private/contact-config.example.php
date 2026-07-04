<?php
/**
 * VOORBEELDCONFIGURATIE — kopieer naar contact-config.php en vul in.
 *
 * Plaats het echte bestand BUITEN de webroot, bijvoorbeeld:
 *   /domains/geborgdadvies.nl/private/contact-config.php
 *
 * Dit .example-bestand bevat GEEN echte gegevens en mag in git staan.
 * Het echte contact-config.php staat in .gitignore.
 */

return [
    // SMTP-server
    'smtp_host'      => 'mail.geborgdadvies.nl',
    'smtp_port'      => 465,          // SMTPS
    'smtp_user'      => 'website@geborgdadvies.nl',
    'smtp_password'  => 'VUL_HIER_HET_WACHTWOORD_IN',

    // Vast afzenderadres (moet het geauthenticeerde account zijn i.v.m. SPF/DKIM)
    'from_email'     => 'website@geborgdadvies.nl',
    'from_name'      => 'Geborgd Advies',

    // Ontvanger van de contactberichten
    'recipient'      => 'edwin@geborgdadvies.nl',
    'recipient_name' => 'Edwin',

    // Schrijfbare map voor rate-limiting-bestanden.
    // Laat leeg ('') om de systeem-temp-map te gebruiken.
    'storage_dir'    => '',
];
