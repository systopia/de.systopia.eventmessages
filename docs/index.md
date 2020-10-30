# Overview

## Scope

This extension provides an alternative way to send confirmation emails for
CiviCRM Events. It creates an additional tab within CiviCRM Events that allows
you to define different message templates to be sent based on conditions
(including the participants status, role and preferred language). It will also
allow you to suppress CiviCRM's regular emails.

It aims at providing an easier way to adapt event confirmation mails than
editing the system workflow messages provided by CiviCRM.

![Configuration Mask](img/CiviCRM_Event_Communication.png?raw=true
"Configuration Mask")

## Known Issues

This extension can send emails whenever an event registration is  created or
updated, including regular CiviEvent registration forms. You will be able to use
many of CiviCRM's regular token as well as some special token provided by the
extension. However some token and other data that can be found in CiviCRM's
default confirmation mails won't work out of the box. This particularly affects
payment information for events with online payments and/or participant fees.
