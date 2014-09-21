<?php

/**
 * kitFramework::flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 *
 * This file was created by the kitFramework i18nEditor
 */

if ('á' != "\xc3\xa1") {
    // the language files must be saved as UTF-8 (without BOM)
    throw new \Exception('The language file ' . __FILE__ . ' is damaged, it must be saved UTF-8 encoded!');
}

return array(
  '- all categories -'
    => '- alle Kategorien -',
  '- Articles only -'
    => '- nur Artikel -',
  '- Event items only -'
    => '- nur Veranstaltungen -',
  '- FAQ items only -'
    => '- nur FAQ Einträge -',
  '- Glossary items only -'
    => '- nur Glossar Einträge -',
  '- new entry -'
    => '- neuer Datensatz -',
  '[flexContent] Successfull installed flexContent as CMS Page Section'
    => '[flexContent] Erfolgreich als CMS Seitenabschnitt installiert',
  'A brief description of this RSS Channel'
    => 'Kurze Beschreibung dieses RSS Kanal',
  'A title for this RSS Channel'
    => 'Bezeichnung für diesen RSS Kanal',
  'Action'
    => 'Ausführen',
  'Archive from'
    => 'Archivieren ab',
  'Are you sure to delete this entry?'
    => 'Sind Sie sicher, dass Sie diesen Eintrag löschen wollen?',
  'Associated the tag %tag% to this flexContent.'
    => 'Der Hashtag <b>%tag%</b> wurde diesem flexContent Inhalt zugeordnet.',
  'At least must it exists some text within the teaser or the content, at the moment the Teaser and the Content are empty!'
    => 'Es muss zumindest im Anreisser oder im Inhalt ein Text vorhanden sein, momentan sind beide Felder leer!',
  'Breaking'
    => 'Hervorgehoben',
  'Breaking to'
    => 'Hervorheben bis',
  'Can not handle the requested redirect at this place - use the <a href="%permalink%" target="_blank">permanent link</a> instead!'
    => 'Die angeforderte Weiterleitung kann an dieser Stelle nicht ausgeführt werden - nutzen Sie diesen <a href="%permakink%" target="_blank">permanenten Link</a>!',
  'Can\'t read the content data from the given import ID %import_id%.'
    => 'Kann die Inhalte von der angegebenen Import ID %import_id% nicht lesen!',
  'Category'
    => 'Kategorie',
  'Category description'
    => 'Beschreibung',
  'Category id'
    => 'ID',
  'Category name'
    => 'Kategorie',
  'Category permalink'
    => 'Permanentlink',
  'Category type'
    => 'Typ',
  'Changed status of import ID %import_id% to <i>ignore</i>'
    => 'Status der Import ID %import_id% auf <em>ignorieren</em> geändert.',
  'Changed status of import ID %import_id% to <i>pending</i>'
    => 'Status der Import ID %import_id% auf <em>anstehend</em> geändert.',
  'Channel category'
    => 'Kategorie',
  'Channel copyright'
    => 'Urheberrecht',
  'Channel description'
    => 'Beschreibung',
  'Channel id'
    => 'Kanal ID',
  'Channel image'
    => 'Abbildung',
  'Channel limit'
    => 'Limit',
  'Channel link'
    => 'Permanenter Link',
  'Channel title'
    => 'Titel',
  'Channel webmaster'
    => 'Webmaster',
  'Check kitcommand'
    => 'kitCommand prüfen',
  'Click to select articles from category'
    => 'Anklicken um Artikel aus dieser Kategorie auszuwählen',
  'Click to start search'
    => 'Anklicken um die Suche auszuführen',
  'Connection is not authenticated, please check name and token!'
    => 'Die Verbindung ist nicht autorisiert, bitte prüfen Sie den Namen und die Kennung (Token)',
  'Content'
    => 'Inhalt',
  'Content categories'
    => 'Inhalte',
  'Content id'
    => 'ID',
  'Copied kitCommand to clipboard:'
    => 'kitCommand in die Zwischenablage kopiert:',
  'Copy the complete kitCommand<br /><code>%command%</code><br />to clipboard. Purpose:<br />%purpose%.'
    => 'Das vollständige kitCommand<br /><code>%command%</code><br />in die Zwischenablage kopieren. Verwendungszweck:<br />%purpose%.',
  'Create a new article'
    => 'Einen neuen Artikel erstellen',
  'Create a new category'
    => 'Eine neue Kategorie anlegen',
  'Create a new RSS Channel'
    => 'Einen neuen RSS Kanal erstellen',
  'Create a new tag'
    => 'Einen neuen Hashtag erstellen',
  'Create article'
    => 'Artikel anlegen',
  'Create or edit a flexContent article'
    => 'Erstellen oder Bearbeiten Sie einen flexContent Artikel',
  'Create or edit categories'
    => 'Erstellen oder Bearbeiten Sie flexContent Kategorien',
  'Create or edit hashtags'
    => 'Erstellen oder Bearbeiten Sie #Hashtags',
  'Created the new tag %tag% and attached it to this content.'
    => 'Der Hashtag <b>%tag%</b> wurde erstellt und diesem flexContent Inhalt zugeordnet.',
  'Csv file'
    => 'CSV Datei',
  'Data handling'
    => 'Datenbearbeitung',
  'DATA_CLEAN_UP'
    => 'behält die HTML Struktur bei, entfernt jedoch alle Klassen und Formatierungsangaben (empfohlen)',
  'DATA_STRIP_TAGS'
    => 'entfernt alle HTML Formatierungen und importiert den Inhalt als blanken Text',
  'DATA_UNCHANGED'
    => 'keine Änderung des importierten Inhalt',
  'dbGlossary CSV file'
    => 'dbGlossary CSV Datei',
  'delete this category type'
    => 'diese Kategorie löschen - <strong>Vorsicht</strong>: Artikel die diese Kategorie <strong>primär</strong> nutzen werden ebenfalls gelöscht!',
  'Delete this tag from database'
    => 'dieses Schlagwort aus der Datenbank entfernen',
  'delete this tag type'
    => 'diesen Hashtag löschen',
  'Edit article'
    => 'Artikel bearbeiten',
  'Event date from'
    => 'Beginn der Veranstaltung',
  'Event date to'
    => 'Ende der Veranstaltung',
  'Event location'
    => 'Veranstaltungsort',
  'Event organizer'
    => 'Veranstalter',
  'Execute the import'
    => 'Import durchführen',
  'Fatal error: Missing FAQ IDs or a Category ID!'
    => 'Fataler Fehler: Es fehlt die FAQ ID oder eine Kategorie ID!',
  'Fatal error: Missing the category ID!'
    => 'Fataler Fehler: die Kategorie ID wurde nicht übergeben!',
  'Fatal error: Missing the content ID!'
    => 'Fataler Fehler: die flexContent ID wurde nicht übergeben!',
  'Fatal error: Missing the Tag ID!'
    => 'Fataler Fehler: Es fehlt die ID für das Schlagwort!',
  'flexContent - About'
    => 'flexContent - Über',
  'flexContent - Category types'
    => 'flexContent - Kategorie Typen',
  'flexContent - Content list'
    => 'flexContent - Artikelliste',
  'flexContent - Create or edit a category'
    => 'flexContent - Kategorie erstellen oder bearbeiten',
  'flexContent - Create or edit a tag'
    => 'flexContent - Schlagwort erstellen oder bearbeiten',
  'flexContent - Create or Edit Content'
    => 'flexContent - Artikel erstellen oder bearbeiten',
  'flexContent - Import control list'
    => 'flexContent - Import Kontrollliste',
  'flexContent - Tag types'
    => 'flexContent - Schlagwort Typen',
  'flexContent category'
    => 'flexContent Kategorie',
  'flexContent Server Info for <em>%server_name%</em>'
    => 'flexContent Server Information für <em>%server_name%</em>',
  'Glossary'
    => 'Glossar',
  'Got no file handle for <var>%file%</var>.'
    => 'Erhielt keinen Zugriff auf <var>%file%</var>.',
  'Hashtags'
    => 'Schlagwörter',
  'Headline'
    => 'Schlagzeile',
  'Hidden'
    => 'Versteckt',
  'If you are providing multiple RSS Channels you can also define a category'
    => 'Wenn Sie mehrere RSS Kanäle betreiben können Sie zusätzlich eine Kategorie angeben',
  'Ignore this item'
    => 'Artikel ignorieren',
  'Import again'
    => 'Import wiederholen',
  'Import CSV file from previous dbGlossary installation'
    => 'CSV Datei aus einer früheren dbGlossary Installation importieren',
  'Import id'
    => 'ID',
  'Import now'
    => 'Artikel jetzt übernehmen',
  'Import WYSIWYG and Blog contents'
    => 'Übernehmen Sie WYSIWYG oder Blog Artikel in flexContent',
  'In the target URL <strong>%target_url%</strong> for the category <strong>%category_name%</strong> is the kitCommand <strong>~~ flexContent action[category] category_id[%category_id%] ~~</strong> needed!'
    => 'In der Ziel URL <strong>%target_url%</strong> für die Kategorie <strong>%category_name%</strong> wird das kitCommand <strong>~~ flexContent action[category] category_id[%category_id%] ~~</strong> benötigt, bitte einfügen!',
  'Information about the flexContent extension'
    => 'Information über die flexContent Erweiterung',
  'Keywords'
    => 'Schlüsselbegriffe',
  'Language'
    => 'Sprache',
  'Link'
    => 'Link',
  'List of all flexContent articles'
    => 'Liste aller flexContent Artikel',
  'Mark as pending'
    => 'Als anstehend markieren',
  'Modified'
    => 'letzte Änderung',
  'Next article'
    => 'Nächster Artikel',
  'Next page'
    => 'Nächste Seite',
  'NL'
    => 'Niederländisch',
  'No active content available!'
    => 'Kein aktiver Inhalt verfügbar!',
  'No category available for the language %language%, please create a category first!'
    => 'Es existiert keine Kategorie für die Sprache %language%, bitte erstellen Sie zunächst eine Kategorie!',
  '<p>No <em>dbGlossary</em> items imported.</p><p>Skipped <em>dbGlossary redirects</em>: %skipped_redirect% items,<br>Skipped already existing items: %skipped_existing%,<br>Skipped due <em>permanent link</em> conflict: %skipped_permanentlink%.</p><p>Check protocol for detailed information.</p>'
    => '<p>Es wurden keine <em>dbGlossary</em> Einträge importiert.</p><p>Übersprungene <em>dbGlossary Weiterleitungen</em>:%skipped_redirect%,<br>Bereits existierende Einträge übersprungen: %skipped_existing%,<br>Überprungen wegen <em>Permanent Link</em> Konflikten: %skipped_permanentlink%.</p><p>Prüfen Sie das Protokoll für detailierte Informationen.</p>',
  'Oooops, can not evaluate the mime type of the uploaded file, abort submission.'
    => 'Hoppla, kann den MIME Typ der übertragenen Datei nicht ermitteln, Import abgebrochen.',
  'Oooops, got a server response, but don\'t know how to handle it: %response%'
    => 'Hoppla, habe eine Server Antwort erhalten, weiß aber nicht wie diese zu handhaben ist: %response%',
  'Organize and present contents in a flexible way'
    => 'Inhalte flexible organisieren und präsentieren',
  'Organize RSS Feeds for the flexContent articles'
    => 'Organisieren Sie RSS Feeds für die flexContent Artikel',
  'Overview'
    => 'Übersicht',
  'Page title'
    => 'Seitentitel',
  'Permalink'
    => 'Permanenter Link',
  'Permalink url'
    => 'Permanentlink, URL',
  'Permanent link to this article'
    => 'Permanenter Link auf diesen Artikel',
  'Please check the parameter content_exposed, allowed values are only 0,1,2,3,4,6 or 12!'
    => 'Bitte überprüfen Sie den Parameter <em> content_exposed</em>, erlaubte Werte sind 0, 1, 2, 3, 4, 6 oder 12!',
  'Please check the <strong>server url</strong> for the remote connection, actual %url% is not valid!'
    => 'Bitte überprüfen Sie die <strong>Server URL</strong> für die entfernte Verbindung, momentan ist diese nicht gültig!',
  'Please check the status, the value %value% is invalid!'
    => 'Bitte überprüfen Sie den Status, der Wert %value% ist ungültig!',
  'Please create a flexContent category of type <var>GLOSSARY</var> before you import CSV data from dbGlossary.'
    => 'Bitte legen Sie eine <em>flexContent</em> Kategorie vom Typ <var>Glossar</var> an, bevor Sie CSV Daten aus <em>dbGlossary</em> übernehmen.',
  'Please define between %minimum% and %maximum% keywords, actual: %count%'
    => 'Bitte definieren Sie zwischen %minimum% und %maximum% Schlüsselwörter, aktuell sind es: %count%.',
  'Please define keywords for the content'
    => 'Legen Sie bitte Schlüsselwörter für den Inhalt fest!',
  'Please fill in all requested fields before submitting the form!'
    => 'Bitte füllen Sie zunächst alle Pflichtfelder aus!',
  'Please select a category!'
    => 'Bitte wählen Sie eine Kategorie aus!',
  'Please select a content category which is associated to the RSS Channel!'
    => 'Bitte wählen Sie eine Artikel Kategorie aus, die mit dem RSS Kanal verbunden ist!',
  'Please select the language for the new flexContent.'
    => 'Bitte wählen Sie die Sprache, die dem neuen flexContent zugeordnet werden soll.',
  'Please select the language for the new RSS Channel.'
    => 'Bitte wählen Sie die Sprache für den neuen RSS Kanal',
  'Please type in a brief description for the RSS Channel!'
    => 'Bitte geben Sie eine kurze Beschreibung für den RSS Kanal ein!',
  'Please type in a name for the category type.'
    => 'Bitte geben Sie eine Bezeichnung für die Kategorie an.',
  'Please type in a name for the tag type.'
    => 'Bitte geben Sie eine Bezeichnung für den Hashtag an.',
  'Please type in a title for the RSS Channel.'
    => 'Bitte geben Sie einen Titel für den RSS Kanal an!',
  'Previous article'
    => 'Vorheriger Artikel',
  'Previous page'
    => 'Vorherige Seite',
  'Primary category'
    => 'Kategorie',
  'Primary category name'
    => 'Primäre Kategorie',
  'Primary category target url'
    => 'Ziel URL der primären Kategorie',
  'Problem: \'%first%\' must be defined before \'%second%\', please check the configuration file!'
    => 'Problem: \'%first%\' muss vor dem Eintrag \'%second%\' festgelegt werden, bitte prüfen Sie die Konfiguration!',
  'Publish from'
    => 'Veröffentlichen ab',
  'Published'
    => 'Veröffentlicht',
  'read more'
    => 'mehr',
  'Redirect target'
    => 'Ziel',
  'Redirect url'
    => 'Umleitung auf URL',
  'Remove dbGlossary ||tags||'
    => 'dbGlossary <b>||</b>Markierungen<b>||</b> entfernen',
  'Remove from list'
    => 'Aus der Liste entfernen',
  'Remove image'
    => 'Bild entfernen',
  'Rss'
    => 'RSS',
  'RSS'
    => 'RSS',
  'RSS Channel Image'
    => 'Abbildung',
  'Save'
    => 'Speichern',
  'Save changes'
    => 'Änderungen übernehmen',
  'search'
    => 'Suchen',
  'Secondary categories'
    => 'Zusätzliche Kategorien',
  'Select category type image'
    => 'Bild auswählen',
  'Select tag type image'
    => 'Bild auswählen',
  'Select teaser image'
    => 'Bild auswählen',
  'Select the categories which are assigned to this RSS Channel'
    => 'Wählen Sie die Kategorien aus, die diesem RSS Kanal zugeordnet werden sollen.',
  'SEO: Description'
    => 'SEO: Description',
  'SEO: Keywords'
    => 'SEO: Keywords',
  'SEO: Page title'
    => 'SEO: Page title',
  'Server Request failed, returned status code: %status%'
    => 'Die Server Anfrage ist fehlgeschlagen, zurückgegebener Status: %status%',
  'Show the actual articles in a overview'
    => 'Die aktuellen Artikel in einer Übersicht anzeigen',
  'Show the contents of the category'
    => 'Die Artikel der Kategorie anzeigen',
  'Show the description of the category and all assigned articles'
    => 'Die Beschreibung der Kategorie und alle zugeordneten Artikel anzeigen',
  'Show the description of the hashtag and all assigned articles'
    => 'Die Beschreibung des Hashtag und alle zugeordneten Artikel anzeigen',
  'Show this content as single article'
    => 'Diesen Inhalt als einzelnen Artikel anzeigen',
  'Sorry, but the RSS Channel %title% does not contain any feeds!'
    => 'Entschuldigung, aber der RSS Kanal %title% enthält keine Einträge!',
  'Sorry, but the RSS Channel %title% is currently not available!'
    => 'Entschuldigung, der RSS Kanal %title% ist momentan nicht verfügbar!',
  'Succesfull updated the flexContent record with the ID %id%'
    => 'Der flexContent Datensatz mit der ID %id% wurde aktualisiert.',
  '<p>Successful imported <strong>%items%</strong> <em>dbGlossary</em> items.</p><p>Skipped <em>dbGlossary redirects</em>: %skipped_redirect% items,<br>Skipped already existing items: %skipped_existing%,<br>Skipped due <em>permanent link</em> conflict: %skipped_permanentlink%.</p><p>Check protocol for detailed information.</p>'
    => '<p>Es wurden insgesamt <strong>%items%</strong> <em>dbGlossary</em> Einträge importiert.</p><p>Übersprungene <em>dbGlossary Weiterleitungen</em>:%skipped_redirect%,<br>Bereits existierende Einträge übersprungen: %skipped_existing%,<br>Überprungen wegen <em>Permanent Link</em> Konflikten: %skipped_permanentlink%.</p><p>Prüfen Sie das Protokoll für detailierte Informationen.</p>',
  'Successfull create the new category type %category%.'
    => 'Die Kategorie <b>%category%</b> wurde erfolgreich erstellt.',
  'Successfull create the new RSS Channel %title%.'
    => 'Der RSS Kanal <b>%title%</b> wurde erfolgreich erstellt.',
  'Successfull create the new tag type %tag%.'
    => 'Der Hashtag %tag% wurde neu erstellt.',
  'Successfull created a new flexContent record with the ID %id%.'
    => 'Es wurde ein neuer flexContent Datensatz mit der ID %id% angelegt.',
  'Tag (#tag)'
    => 'Hashtag (#hashtag)',
  'Tag description'
    => 'Beschreibung',
  'Tag id'
    => 'Tag ID',
  'Tag ID'
    => 'ID',
  'Tag image'
    => 'Abbildung',
  'Tag name'
    => 'Bezeichner',
  'Tag permalink'
    => 'Permanenter Link',
  'tag_name'
    => 'Bezeichner',
  'Target url'
    => 'Ziel URL',
  'Target URL'
    => 'Ziel URL',
  'Teaser'
    => 'Anreisser',
  'Teaser image'
    => 'Abbildung',
  'The \'publish from\' field is always needed and can not switched off, please check the configuration!'
    => 'Das \'Veröffentlichen ab\' Feld wird immer benötigt und kann in den Einstellungen nicht ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!',
  'The action: %action% is not supported!'
    => 'Die <em>action</em>: <strong>%action%</strong> wird nicht unterstützt!',
  'The Category %category_name% does not contain any active contents'
    => 'Die Kategorie <strong>%category_name%</strong> enthält keine aktiven Inhalte!',
  'The category type %category% already exists and can not inserted!'
    => 'Die Kategorie <b>%category%</b> existiert bereits und kann nicht erneut eingefügt werden!',
  'The category type %category% was successfull deleted.'
    => 'Die Kategorie <b>%category%</b> wurde erfolgreich gelöscht.',
  'The category type list for flexContent is empty, please create the first category!'
    => 'Es existieren noch keine Kategorien, erstellen Sie die erste Kategorie!',
  'The category type name %category% contains the forbidden character %char%, please change the name.'
    => 'Der Kategorie Bezeichner <b>%category%</b> enthält das verbotene Zeichen %char%, bitte ändern Sie die Bezeichnung.',
  'The Category Type record with the ID %id% does not exists!'
    => 'Es existiert kein Kategorie Datensatz mit der ID %id%!',
  'The Category with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!'
    => 'Die Kategorie mit der <strong>ID %id%</strong> existiert nicht für die Sprache <strong>%language%</strong>!',
  'The Channel Link %channel_link% is already in use by the RSS Channel record %id%, please select another one!'
    => 'Der RSS Kanal Link %channel_link% wird bereits von dem RSS Kanal mit der ID %id% verwendet, bitte wählen Sie einen anderen!',
  'The CMS target URL does not contain the needed kitCommand!'
    => 'Die CMS Ziel URL enthält nicht das benötigte kitCommand!',
  'The date and time for the event where set automatically, you must check them!'
    => 'Datum und Uhrzeit für die Veranstaltung wurden automatisch gesetzt, Sie müssen die Angaben prüfen!',
  'The description should have a length between %minimum% and %maximum% characters (actual: %length%).'
    => 'Die Beschreibung sollte eine Länge zwischen %minimum% und %maximum% Zeichen haben, zur Zeit sind es %length% Zeichen.',
  'The event ending date %event_date_to% is less then the event starting date %event_date_from%!'
    => 'Das Datum für das Veranstaltungsende %event_date_to% liegt nach dem Beginn der Veranstaltung am %event_date_from%!',
  'The event starting date %event_date_from% is less then the content publish from date %publish_from%, this is not allowed!'
    => 'Das Datum für den Beginn der Veranstaltung am %event_date_from% liegt vor dem Datum für die Veröffentlichung dieses Artikel am %publish_from%! Bitte prüfen Sie Ihre Angaben.',
  'The field %name% can not be empty!'
    => 'Das Feld %name% darf nicht leer sein!',
  'The flexContent list is empty, please create your first content!'
    => 'Es existieren noch keine flexContent Inhalte, erstellen Sie einen ersten Artikel!',
  'The flexContent record with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!'
    => 'Es existiert kein flexContent Datensatz mit der <strong>ID %id%</strong> für die Sprache <strong>%language%</strong>!',
  'The flexContent record with the ID %id% does not exists!'
    => 'Es existiert kein flexContent Datensatz mit der ID %id%!',
  'The headline is always needed and can not switched off, please check the configuration!'
    => 'Die Schlagzeile wird immer benötigt und kann nicht in den Einstellungen ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!',
  'The headline should have a length between %minimum% and %maximum% characters (actual: %length%).'
    => 'Die Schlagzeile sollte eine Länge zwischen %minimum% und %maximum% Zeichen haben, zur Zeit sind es %length% Zeichen.',
  'The image %image% was successfull inserted.'
    => 'Das Bild %image% wurde dem Datensatz hinzugefügt.',
  'The image was successfull removed.'
    => 'Das Bild wurde entfernt.',
  'The list of RSS Channels for flexContent is empty, please create the first RSS Channel!'
    => 'Es existieren noch keine RSS Kanäle, erstellen Sie den ersten RSS Kanal!',
  'The page title should have a length between %minimum% and %maximum% characters (actual: %length%).'
    => 'Der Seitentitel sollte eine Länge zwischen %minimum% und %maximum% Zeichen haben, zur Zeit sind es %length% Zeichen.',
  'The parameter action[%action%] is not supported for remote connections!'
    => 'Der Parameter action[%action%] wird für entfernte Verbindungen nicht unterstützt!',
  'The parameter <var>check_jquery[]</var> is no longer available, use <var>load_jquery[]</var> instead.'
    => 'Der Parameter <var>check_jquery[]</var> ist nicht mehr verfügbar, bitte nutzen Sie stattdessen <var>load_jquery[]</var>.',
  'The permalink <b>%permalink%</b> does not exists!'
    => 'Der PermanentLink <b>%permalink%</b> existiert nicht!',
  'The permalink %permalink% is already in use by the category type record %id%, please select another one!'
    => 'Der Permanentlink %permalink% wird bereits von der Kategorie mit der ID %id% verwendet, bitte wählen Sie einen anderen Link!',
  'The permalink %permalink% is already in use by the flexContent record %id%, please select another one!'
    => 'Der PermanentLink <b>%permalink%</b> wird bereits von dem flexContent Datensatz <b>%id%</b> verwendet, bitte wählen Sie einen anderen permanenten Link aus!',
  'The permalink %permalink% is already in use by the tag type record %id%, please select another one!'
    => 'Der Permanentlink %permalink% wird bereits von dem Schlagwort mit der ID %id% verwendet, bitte wählen Sie einen anderen Link.',
  'The permalink %permalink% is already in use, please select another one!'
    => 'Der PermanentLink <b>%permalink%</b> wird bereits verwendet, bitte wählen Sie einen anderen permanenten Link aus!',
  'The permalink <b>/buzzword/%permalink%</b> does not exists!'
    => 'Der Permanentlink <b>/buzzword/%permalink%</b> existiert nicht!',
  'The permalink <b>/category/%permalink%</b> does not exists!'
    => 'Der Permanentlink <b>/category/%permalink%</b> existiert nicht!',
  'The permanent link is always needed and can not switched off, please check the configuration!'
    => 'Der PermanentLink wird immer benötigt und kann in den Einstellungen nicht ausgeschaltet werden. Bitte prüfen Sie die Konfiguration!',
  'The RSS Channel <b>/%language%/rss/%permalink%</b> does not exists or is not active!'
    => 'Der RSS Kanal <b>%language%/rss/%permalink%</b> existiert nicht oder ist nicht aktiv.',
  'The RSS Channel record with the ID %id% does not exists!'
    => 'Es existiert kein Datensatz für den RSS Kanal mit der ID %id%!',
  'The server is missing the definition of the allowed categories for the client'
    => 'Der Server vermisst die Definition der erlaubten Kategorien für den Client.',
  'The server response is empty!'
    => 'Die Server Antwort enthielt keine Daten!',
  'The tag %name% is not assigned to any content!'
    => 'Das Schlüsselwort %name% ist keinem Artikel zugeordnet!',
  'The tag %old% was changed to %new%. This update will affect all contents.'
    => 'Der Hashtag <b>%old%</b> wurde zu <b>%new%</b> geändert. Diese Aktualisierung wirkt sich auf alle flexContent Inhalte aus.',
  'The tag %tag% is no longer associated with this content.'
    => 'Der Hashtag <b>%tag%</b> ist diesem flexContent Inhalt nicht mehr zugeordnet.',
  'The tag %tag% was successfull deleted and removed from all content.'
    => 'Der Hashtag <b>%tag%</b> wurde gelöscht und bestehende Zuordnungen zu flexContent Inhalten entfernt.',
  'The tag %tag_name% does not contain any active contents'
    => 'Der Hashtag %tag_name% enthält keine aktiven Inhalte!',
  'The tag type %tag% already exists and can not inserted!'
    => 'Die Hashtag <b>%tag%</b> existiert bereits und kann nicht zusätzlich eingefügt werden!',
  'The tag type %tag% was successfull deleted.'
    => 'Das Schlüsselwort %tag% wurde erfolgreich gelöscht.',
  'The tag type list for flexContent is empty, please create a tag!'
    => 'Es existieren noch keine Hashtags für flexContent, bitte legen Sie einen Hashtag an!',
  'The tag type name %tag% contains the forbidden character %char%, please change the name.'
    => 'Der Hashtag <b>%tag%</b> enthält das verbotene Zeichen <b>%char%</b>, bitte ändern Sie die Bezeichnung.',
  'The Tag Type record with the ID %id% does not exists!'
    => 'Es existiert kein Tag Type Datensatz mit der ID %id%!',
  'The Tag with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!'
    => 'Der Hashtag mit der <strong >ID %id%</strong> existiert nicht für die Sprache <strong>%language%</strong>!',
  'The target URL assigned to this permanent link does not exists!'
    => 'Die Ziel URL die diesem Permanentlink zugeordnet ist, existiert nicht!',
  'There a no tags available for a listing!'
    => 'Es sind keine Schlagworte für eine Auflistung verfügbar!',
  'There are more records using this tag, are you sure do you want to remove it?'
    => 'Dieser Schlüsselbegriff wird von mehreren Datensätzen verwendet, sind Sie sicher, dass Sie ihn entfernen möchten?',
  'There is no category assigned to this pemanent link!'
    => 'Diesem Permanentlink ist keine Kategorie zugewiesen!',
  'There is no content assigned to this pemanent link!'
    => 'Diesem Permanentlink ist kein Artikel zugeordnet!',
  'There is no tag assigned to this pemanent link!'
    => 'Diesem Permanentlink ist kein Schlagwort zugeordnet!',
  'There is no target URL assigned to this pemanent link!'
    => 'Diesem Permanentlink ist keine Ziel URL zugeordnet!',
  'There was no image selected.'
    => 'Es wurde kein Bild ausgewählt.',
  'This Element will be deleted'
    => 'Dieses Element wird gelöscht.',
  'This list does not contain any contents!'
    => 'Diese Liste enthält keine Artikel!',
  'This selection does not contain any items!'
    => 'Diese Auswahl enthält keine Einträge!',
  'To make use of the <strong>remote</strong> feature you must define a <strong>remote</strong> section in the config.flexcontent.json!'
    => 'Um das <strong>remote</strong> Feature nutzen zu können, müssen Sie zunächst einen <strong>remote</strong> Abschnitt in der <em>config.flexcontent.json</em> definieren.',
  'To use a category of type <var>EVENT</var> you need to install the kitFramework extension <strong>Contact</strong> first.'
    => 'Um eine Kategorie vom Typ <var>Veranstaltung</var> verwenden zu können, müssen Sie zunächst die kitFramework Erweiterung <strong>Contact</strong> installieren.',
  'Unexpected structure of the dbGlossary CSV file!'
    => 'Unerwartete Struktur der übermittelten dbGlossary CSV Datei!',
  'Unpublished'
    => 'Unveröffentlicht',
  'Updated the category type %category%'
    => 'Die Kategorie <b>%category%</b> wurde aktualisiert.',
  'Updated the RSS Channel %title%.'
    => 'Der RSS Kanal %title% wurde aktualisiert.',
  'Updated the tag type %tag%'
    => 'Der Hashtag <b>%tag%</b> wurde aktualisiert.',
  'Used by content id'
    => 'Verwendet von',
  'You can specify a copyright hint for the RSS Channel'
    => 'Sie können einen Copyright Hinweis für die Artikel des RSS Kanal angeben',
  'You can specify the email address of the webmaster for this RSS Channel'
    => 'Sie können optional die E-Mail Adresse des für diesen RSS Kanal zuständigen Webmaster angeben.',
  'You must define the <strong>client name</strong> for the remote connection in the config.flexcontent.json!'
    => 'Sie müssen einen <strong>client name</strong für die entfernte Verbindung in der <em>config.flexcontent.json</em> festlegen!',
  'You must specify the <strong>server name</strong> for the remote connection in the config.flexcontent.json!'
    => 'Sie müssen einen <strong>Servernamen</strong> für die entfernte Verbindung in der <em>config.flexcontent.json</em> festlegen!',
  'You must specify the <strong>server url</strong> for the remote connection in the config.flexcontent.json!'
    => 'Sie müssen eine <strong>Server URL</strong> für die entfernte Verbindung in der <em>config.flexcontent.json</em> festlegen!',
  'You must specify the <strong>token</strong> for the remote connection in the config.flexcontent.json!'
    => 'Sie müssen eine <strong>Kennung</strong> (token) für die entfernte Verbindung in der <em>config.flexcontent.json</em> festlegen!',
);
