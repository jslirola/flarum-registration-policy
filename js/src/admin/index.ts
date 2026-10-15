import app from 'flarum/admin/app';
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';

const t = (key: string) => app.translator.trans(`jslirola-registration-policy.admin.settings.${key}`);

app.initializers.add('jslirola-registration-policy', () => {
  const registry = app.registry.for('jslirola-registration-policy').registerSetting({
    setting: 'jslirola-registration-policy.block_token_unconfirmed',
    type: 'boolean',
    label: t('block_token_unconfirmed_label'),
    help: t('block_token_unconfirmed_help'),
  });

  // Only meaningful next to maicol07/flarum-ext-sso: the listener it controls is
  // not even registered without it (see extend.php), so do not offer the switch.
  if (isExtensionEnabled('maicol07-sso')) {
    registry.registerSetting({
      setting: 'jslirola-registration-policy.override_sso_activation',
      type: 'boolean',
      label: t('override_sso_activation_label'),
      help: t('override_sso_activation_help'),
    });
  }
});
