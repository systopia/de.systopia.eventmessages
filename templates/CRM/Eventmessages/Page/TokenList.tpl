{*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.eventmessages'}
  <h3>{ts}This is a list of all <i>possible</i> tokens to be used in the EventMessages emails.{/ts}</h3>

  <table>
    <thead>
    <tr>
      <th>{ts}Token{/ts}</th>
      <th>{ts}Description{/ts}</th>
    </tr>
    </thead>

    <tbody>
    {foreach from=$token_list key=token item=description}
      <tr>
        <td><code>{literal}{{/literal}{$token}{literal}}{/literal}</code></td>
        <td>{$description}</td>
      </tr>
    {/foreach}
    </tbody>
  </table>

{/crmScope}

