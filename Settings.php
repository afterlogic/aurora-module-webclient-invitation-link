<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\InvitationLinkWebclient;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property string $RegisterModuleName
 * @property string $LoginModuleName
 * @property bool $EnableSendInvitationLinkViaMail
 * @property string $NotificationType
 * @property string $NotificationEmail
 * @property string $NotificationHost
 * @property string $NotificationPort
 * @property bool $NotificationUseAuth
 * @property string $NotificationLogin
 * @property string $NotificationPassword
 * @property string $SMTPSecure
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "Setting to true disables the module",
            ),
            "RegisterModuleName" => new SettingsProperty(
                "StandardRegisterFormWebclient",
                "string",
                null,
                "Denotes the module used for providing signup interface",
            ),
            "LoginModuleName" => new SettingsProperty(
                "StandardLoginFormWebclient",
                "string",
                null,
                "Denotes the module used for providing login interface",
            ),
            "EnableSendInvitationLinkViaMail" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, the module will be sending invitation links via email",
            ),
            "NotificationType" => new SettingsProperty(
                "mail",
                "string",
                null,
                "Denotes how the mail is sent - mail for standard mail() function of PHP, smtp for sending via SMTP protocol",
            ),
            "NotificationEmail" => new SettingsProperty(
                "mail@localhost",
                "string",
                null,
                "Sender email address used in mail messages sent by this module",
            ),
            "NotificationHost" => new SettingsProperty(
                "localhost",
                "string",
                null,
                "SMTP server host used for sending mail by this module",
            ),
            "NotificationPort" => new SettingsProperty(
                "25",
                "string",
                null,
                "SMTP server port number",
            ),
            "NotificationUseAuth" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, SMTP authentication is used to connect to SMTP server",
            ),
            "NotificationLogin" => new SettingsProperty(
                "",
                "string",
                null,
                "Username used to authenticate on SMTP server",
            ),
            "NotificationPassword" => new SettingsProperty(
                "",
                "string",
                null,
                "Password used to authenticate on SMTP server",
            ),
            "SMTPSecure" => new SettingsProperty(
                "",
                "string",
                null,
                "Set to 'ssl' or 'tls' to use SSL or STARTTLS respectively",
            ),
        ];
    }
}
