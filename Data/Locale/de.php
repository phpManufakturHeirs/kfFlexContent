<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if ('á' != "\xc3\xa1") {
    // the language files must be saved as UTF-8 (without BOM)
    throw new \Exception('The language file ' . __FILE__ . ' is damaged, it must be saved UTF-8 encoded!');
}

return array(
    '- new entry -'
        => '- neuer Datensatz -',

    'Archive from'
        => 'Archivieren ab',
    'ARCHIVED'
        => 'Archiviert',
    'Associated the tag %tag% to this flexContent.'
        => 'Die Markierung <b>%tag%</b> wurde diesem flexContent Inhalt zugeordnet.',
    'At least must it exists some text within the teaser or the content, at the moment the Teaser and the Content are empty!'
        => 'Es muss zumindest im Anreisser oder im Inhalt ein Text vorhanden sein, momentan sind beide Felder leer!',

    'BREAKING'
        => 'Aktuelle Meldung',
    'Breaking to'
        => 'Hervorheben bis',

    'category_description'
        => 'Beschreibung',
    'category_id'
        => 'Category ID',
    'category_name'
        => 'Bezeichner',
    'Content'
        => 'Inhalt',
    'Content ID'
        => 'ID',
    'content_id'
        => 'ID',
    'Create a new tag'
        => 'Eine neue Markierung erstellen',
    'Create or edit a flexContent article'
        => 'Erstellen oder Bearbeiten Sie einen flexContent Artikel',
    'Created the new tag %tag% and attached it to this content.'
        => 'Die Markierung <b>%tag%</b> wurde erstellt und diesem flexContent Inhalt zugeordnet.',

    'DE'
        => 'Deutsch',
    'delete this category type'
        => 'diese Kategorie löschen',
    'delete this tag type'
        => 'diese Markierung löschen',
    'description'
        => 'Beschreibung',
    'Dutch'
        => 'Niederländisch',

    'EN'
        => 'Englisch',
    'English'
        => 'Englisch',

    'FR'
        => 'Französisch',
    'French'
        => 'Französisch',

    'German'
        => 'Deutsch',

    'HIDDEN'
        => 'Versteckt',

    'Information about the flexContent extension'
        => 'Information über die flexContent Erweiterung',

    'Keywords'
        => 'Schlüsselbegriffe',

    'language'
        => 'Sprache',
    'Language'
        => 'Sprache',
    'List of all flexContent articles'
        => 'Liste aller flexContent Artikel',

    'Next article'
        => 'Nächster Artikel',
    'NL'
        => 'Niederländisch',

    'Overview'
        => 'Übersicht',

    'Permalink'
        => 'Permanenter Link',
    'Permanent link to this article'
        => 'Permanenter Link auf diesen Artikel',
    'Please define keywords for the content'
        => 'Legen Sie bitte Schlüsselwörter für den Inhalt fest!',
    'Please fill in all requested fields before submitting the form!'
        => 'Bitte füllen Sie zunächst alle Pflichtfelder aus!',
    'Please type in a name for the category type.'
        => 'Bitte geben Sie eine Bezeichnung für die Kategorie an.',
    'Please type in a name for the tag type.'
        => 'Bitte geben Sie eine Bezeichnung für die Markierung an.',
    'Previous article'
        => 'Vorheriger Artikel',
    'Primary category'
        => 'Kategorie',
    "Problem: '%first%' must be defined before '%second%', please check the configuration file!"
        => "Problem: '%first%' muss vor dem Eintrag '%second%' festgelegt werden, bitte prüfen Sie die Konfiguration!",
    'publish_from'
        => 'Veröffentlichen&nbsp;ab',
    'PUBLISHED'
        => 'Veröffentlicht',

    'Redirect url'
        => 'Umleitung auf URL',
    'Remove from list'
        => 'Aus der Liste entfernen',

    'Save'
        => 'Speichern',
    'Secondary categories'
        => 'Zusätzliche Kategorien',
    'Select category type image'
        => 'Bild auswählen',
    'Select tag type image'
        => 'Bild auswählen',
    'Select teaser image'
        => 'Bild auswählen',
    'Successfull created a new flexContent record with the ID %id%.'
        => 'Es wurde ein neuer flexContent Datensatz mit der ID %id% angelegt.',
    'Successfull create the new category type %category%.'
        => 'Die Kategorie <b>%category%</b> wurde erfolgreich erstellt.',
    'Successfull create the new tag type %tag%.'
        => 'Die Markierung %tag% wurde neu erstellt.',
    'Succesfull updated the flexContent record with the ID %id%'
        => 'Der flexContent Datensatz mit der ID %id% wurde aktualisiert.',
    'Updated the the tag type %tag%'
        => 'Die Markierung %tag% wurde aktualisiert.',

    'Tag description'
        => 'Beschreibung',
    'tag_description'
        => 'Beschreibung',
    'tag_id'
        => 'Tag ID',
    'Tag name'
        => 'Bezeichner',
    'tag_name'
        => 'Bezeichner',
    'Target URL'
        => 'Ziel URL',
    'Teaser'
        => 'Anreisser',
    'The category type %category% already exists and can not inserted!'
        => 'Die Kategorie <b>%category%</b> existiert bereits und kann nicht erneut eingefügt werden!',
    'The category type %category% was successfull deleted.'
        => 'Die Kategory <b>%category%</b> wurde erfolgreich gelöscht.',
    'The category type list for flexContent is empty, please create the first category!'
        => 'Es existieren noch keine Kategorien, erstellen Sie die erste Kategorie!',
    'The category type name %category% contains the forbidden character %char%, please change the name.'
        => 'Der Kategorie Bezeichner <b>%category%</b> enthält das verbotene Zeichen %char%, bitte ändern Sie die Bezeichnung.',
    'The Category Type record with the ID %id% does not exists!'
        => 'Es existiert kein Kategorie Datensatz mit der ID %id%!',
    'The description should have a length between %minimum% and %maximum% characters (actual: %length%).'
        => 'Die Beschreibung sollte eine Länge zwischen %minimum% und %maximum% Zeichen haben, zur Zeit sind es %length% Zeichen.',
    'The flexContent record with the ID %id% does not exists!'
        => 'Es existiert kein flexContent Datensatz mit der ID %id%!',
    'The flexContent record with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!'
        => 'Es existiert kein flexContent Datensatz mit der <strong>ID %id%</strong> für die Sprache <strong>%language%</strong>!',
    'The image %image% was successfull inserted.'
        => 'Das Bild %image% wurde dem Datensatz hinzugefügt.',
    'The permalink %permalink% is already in use, please select another one!'
        => 'Der PermanentLink <b>%permalink%</b> wird bereits verwendet, bitte wählen Sie einen anderen permanenten Link aus!',
    'The permalink %permalink% is already in use by the flexContent record %id%, please select another one!'
        => 'Der PermanentLink <b>%permalink%</b> wird bereits von dem flexContent Datensatz <b>%id%</b> verwendet, bitte wählen Sie einen anderen permanenten Link aus!',
    'The permanent link is always needed and can not switched off, please check the configuration!'
        => 'Der PermanentLink wird immer benötigt und kann in den Einstellungen nicht ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!',
    "The 'publish from' field is always needed and can not switched off, please check the configuration!"
        => "Das 'Veröffentlichen ab' Feld wird immer benötigt und kann in den Einstellungen nicht ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!",
    'The tag %tag% is no longer associated with this content.'
        => 'Die Markierung <b>%tag%</b> ist diesem flexContent Inhalt nicht mehr zugeordnet.',
    'The tag %old% was changed to %new%. This update will affect all contents.'
        => 'Die Markierung <b>%old%</b> wurde zu <b>%new%</b> geändert. Diese Aktualisierung wirkt sich auf alle flexContent Inhalte aus.',
    'The tag %tag% was successfull deleted and removed from all content.'
        => 'Die Markierung <b>%tag%</b> wurde gelöscht und bestehende Zuordnungen zu flexContent Inhalten entfernt.',
    'The tag type %tag% already exists and can not inserted!'
        => 'Die Markierung <b>%tag%</b> existiert bereits und kann nicht zusätzlich eingefügt werden!',
    'The tag type list for flexContent is empty, please create a tag!'
        => 'Es existieren noch keine Markierungen für flexContent, bitte legen Sie eine Markierung an!',
    'The tag type name %tag% contains the forbidden character %char%, please change the name.'
        => 'Die Markierung <b>%tag%</b> enthält das verbotene Zeichen <b>%char%</b>, bitte ändern Sie die Bezeichnung.',
    'The Tag Type record with the ID %id% does not exists!'
        => 'Es existiert kein Tag Type Datensatz mit der ID %id%!',
    'The title is always needed and con not switched off, please check the configuration!'
        => 'Die Überschrift wird immer benötigt und kann nicht in den Einstellungen ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!',
    'The title should have a length between %minimum% and %maximum% characters (actual: %length%).'
        => 'Der Titel sollte eine Länge zwischen %minimum% und %maximum% Zeichen haben, zur Zeit sind es %length% Zeichen.',
    'There was no image selected.'
        => 'Es wurde kein Bild ausgewählt.',

    'UNPUBLISHED'
        => 'Unveröffentlicht',
    'Updated the category type %category%'
        => 'Die Kategorie <b>%category%</b> wurde aktualisiert.',
    'Updated the tag type %tag%'
        => 'Die Markierung <b>%tag%</b> wurde aktualisiert.',
    'used_by_content_id'
        => 'Verwendet in flexContent ID',
);
