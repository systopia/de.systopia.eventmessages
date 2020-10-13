# Konfigurierbare Event-E-Mails

## Ziele und Funktionsumfang

Diese Erweiterung bietet eine alternative Möglichkeit, Bestätigungs-E-Mails für CiviCRM-Veranstaltungen zu versenden. Sie erstellt eine zusätzlichen Reiter innerhalb von CiviCRM-Veranstaltungen, der es Ihnen ermöglicht, verschiedene E-Mails zu definieren, die unter bestimmten Bedingungen (Teilnehmerstatus, Rolle und bevorzugte Sprache) gesendet werden können. Es ermöglicht Ihnen auch, die regulären E-Mails von CiviCRM zu unterdrücken.  

Die Erweiterung bietet eine einfachere Möglichkeit zur Anpassung von Veranstaltungs-E-Mails als die Bearbeitung der von CiviCRM bereitgestellten System-Workflow-Nachrichten.

![Screenshot](images/CiviCRM_Event_Communication_de.png)

Die Erweiterung ist lizenziert unter [AGPL-3.0](LICENSE.txt).

## Installation

Diese Erweiterung wurde noch nicht für die Installation über die Web-Benutzeroberfläche freigeschaltet.

Sysadmins und Entwickler können die `.zip'-Datei für diese Erweiterung [HIER](https://github.com/systopia/de.systopia.eventmessages/releases) herunterladen und dann im CiviCRM Extension-Ordner entpacken. Dann kann sie über die CiviCRM Benutzeroberfläche aktiviert werden. 

## Konfiguration

Nach der Installation gibt es das neue Tab "Veranstaltungskommunikation" in der Veranstaltungskonfiguration.

Sie können hier nun die Standardkommunikation durch CiviCRM für dieses Event deaktivieren, was in den meisten Fällen zu empfehlen ist, wenn man diese Extension stattdessen verwenden möchte.

Konfigurieren sie dann "Absender", als auch "Kopie" und "Antwort An" der zu versendenden E-Mails.  

Dieses System wird die regulären CiviCRM Nachrichtenvorlagen verwenden, Sie sollten also nun zunächst die entsprechenden Nachrichtenvorlagen anlegen.
Dabei können Sie sowohl die CiviCRM-Standardplatzhalter verwenden, als auch ein Reihe von zusätzlichen Token die von der Extension bereitgestellt werden.
Eine List dieser zusätzlichen Tokens finden Sie in der Hilfeblase neben der Auswahl der Nachrichtenvorlagen oder unter  ``civicrm/eventmessages/tokenlist``.

## Bekannte Einschränkungen

Diese Erweiterung kann E-Mails versenden, sobald eine Veranstaltungsregistrierung erstellt oder aktualisiert wird, einschließlich regulärer CiviEvent-Anmeldeformulare. Sie können viele der regulären Token von CiviCRM sowie einige spezielle Token verwenden, die von der Erweiterung bereitgestellt werden. Einige Token und andere Daten, die in den Standardbestätigungs-E-Mails von CiviCRM zu finden sind, werden jedoch nicht funktionieren. Dies betrifft insbesondere Zahlungsinformationen für Veranstaltungen mit Online-Zahlungs- und/oder Teilnehmergebühren.

## Anforderungen

* PHP ab Version 7.0
* CiviCRM 5.2.x
