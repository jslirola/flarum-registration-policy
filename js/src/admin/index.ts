import app from 'flarum/admin/app';

const t = (key: string) => app.translator.trans(`jslirola-registration-policy.admin.settings.${key}`);

app.initializers.add('jslirola-registration-policy', () => {
  app.registry
    .for('jslirola-registration-policy')
    .registerSetting({
      setting: 'jslirola-registration-policy.skip_login_unconfirmed',
      type: 'boolean',
      label: t('skip_login_unconfirmed_label'),
      help: t('skip_login_unconfirmed_help'),
    })
    .registerSetting({
      setting: 'jslirola-registration-policy.block_token_unconfirmed',
      type: 'boolean',
      label: t('block_token_unconfirmed_label'),
      help: t('block_token_unconfirmed_help'),
    })
    .registerSetting({
      setting: 'jslirola-registration-policy.override_sso_activation',
      type: 'boolean',
      label: t('override_sso_activation_label'),
      help: t('override_sso_activation_help'),
    });
});
