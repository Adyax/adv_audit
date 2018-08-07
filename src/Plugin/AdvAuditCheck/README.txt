Simple manual about how to create test plugin.


1) Generate a plugin skeleton specifying module name, the plugin id and the class
As example:
> drupal generate:plugin:skeleton \
    --module="adv_audit" \
    --plugin-id="cron_settings" \
    --class="CronSettingsCheck"

2) Storing information messages

- All messages for test plugin like fail message, success message and description text stored in YML file
/config/messages/messages.yml

You can access to this file via special service like "adv_audit.messages" but not needed to do this without necessary reason.
Also you should fill all mandatory field for you plugin in the file messages.yml
like:
    - description
    - actions
    - impatcts
    - fail
    - success
    - help

3 Plugin structure

Description for plugin anataion

  @AdvAuditCheck(
   id = "cron_settings",
   label = @Translation("Cron settings"),
   category = "performance",
   severity = "critical",
   requirements = {},
   enabled = true,
  )

id - The plugin ID.
label - The human readable name. We will use this in listing of availabled test on pahes
category - The plugin category id. All availabled category described in adv_audit.config.yml
severity - The default level of severity. Can be overridden via plugin settings form.
requirements - The array of requirements needed for plugin. Like list of modules, user, configs. If requirements are not met, the test will mark as failed !!!!
enabled - Status of the plugin. Can be overriden by config form.

The main method of the test plugin where we do all check is ::perform()


This method should return object \Drupal\adv_audit\AuditReason

With needed information about current result of testing like:
 - Test ID (current plugin ID)
 - Status of the test result passed/failled
 - (optional) Short reason about why this test is failed.

