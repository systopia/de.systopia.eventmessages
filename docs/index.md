# Overview

## Scope

This extension provides an alternative way to send confirmation e-mail messages
for CiviCRM Events. It creates an additional tab within the configuration of
CiviCRM Events that allows you to define different message templates to be sent
based on conditions (including the participants status, role and preferred
language). It will also allow you to suppress CiviCRM's regular e-mail messages.

It aims at providing an easier way to adapt event confirmation messages than
editing the system workflow messages provided by CiviCRM.

![Configuration Mask](img/CiviCRM_Event_Communication.png?raw=true
"Configuration Mask")

## Known Issues

### Token Support

This extension can send e-mail messages whenever an event registration is
created or updated, including regular CiviEvent registration forms. You will be
able to use many of CiviCRM's regular tokens as well as some special tokens
provided by the extension. However some tokens and other data that can be found
in CiviCRM's default confirmation messages won't work out of the box. This
particularly affects payment information for events with online payments and/or
participant fees.

### E-Mail Sender

If e-mail messages are sent via this extension to recipients that are
participants of different events (i.e. when performing a SearchKit search for
participants accross several events), the sender address will be the one that
has been added to the communication preferences of the event of the first
recipient. That e-mail address will be used as sender address in the e-mail
messages to all other recipients, no matter the event they are participating in.
