#!/bin/sh
# quick and dirty script to update the .pot file
l10n_tools="../civi_l10n_tools"
${l10n_tools}/bin/create-pot-files-extensions.sh de.systopia.eventmessages ./ l10n
