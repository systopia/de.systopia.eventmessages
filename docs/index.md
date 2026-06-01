# Overview

## Scope

This extension provides an alternative way to send confirmation e-mails for
CiviCRM Events. It creates an additional tab within CiviCRM Events that allows
you to define different message templates to be sent based on conditions
(including the participant's status, role and preferred language). It will also
allow you to suppress CiviCRM's regular e-mails.

It aims at providing an easier way to adapt event confirmation e-mails than
editing the system workflow messages provided by CiviCRM.

![Configuration Mask](img/CiviCRM_Event_Communication.png?raw=true
"Configuration Mask")

## Known Issues

### Token Support

This extension can send e-mails whenever an event registration is created or
updated, including regular CiviEvent registration forms. You will be able to use
many of CiviCRM's regular tokens as well as some special template variables
provided by the extension. However, some tokens and other data that can be found
in CiviCRM's default confirmation e-mails will not work out of the box. This
particularly affects payment information for events with online payments and/or
participant fees.

### E-Mail Sender

If e-mail messages are send via this extension to recipients that are participants
of different events (i.e. when performing a SearchKit search for participants accross
several events), then the e-mail's sender address will be the one that has been added
to the communication preferences of the event of the first recipient. That e-mail
address will be used as sender address in the e-mails to all other recipients, no
matter what event they are participating in.
