import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import SignUpModal from 'flarum/forum/components/SignUpModal';

/** Must match RegisterController::META_KEY. */
const META_KEY = 'jslirolaRegistrationPolicy';

const t = (key: string, params?: Record<string, unknown>) => app.translator.trans(`jslirola-registration-policy.forum.sign_up.${key}`, params);

app.initializers.add('jslirola-registration-policy', () => {
  // Core reloads the page after POST /register, assuming the registrant is now
  // signed in. When the server held the session back until the email is
  // confirmed, a reload would just land a guest on the same page with no
  // explanation — say where to look instead.
  override(SignUpModal.prototype, 'onsubmit', function (this: any, _original: unknown, e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    app
      .request<any>({
        url: app.forum.attribute('baseUrl') + '/register',
        method: 'POST',
        body: this.submitData(),
        errorHandler: this.onerror.bind(this),
      })
      .then((response) => {
        if (response?.meta?.[META_KEY]?.confirmationRequired) {
          this.registrationPolicyPendingEmail = this.email();
          this.loaded();
          return;
        }

        window.location.reload();
      }, this.loaded.bind(this));
  });

  override(SignUpModal.prototype, 'title', function (this: any, original: () => unknown) {
    return this.registrationPolicyPendingEmail ? t('confirmation_title') : original();
  });

  override(SignUpModal.prototype, 'body', function (this: any, original: () => unknown) {
    if (!this.registrationPolicyPendingEmail) {
      return original();
    }

    return (
      <div className="Form Form--centered">
        <p className="helpText">{t('confirmation_text', { email: <strong>{this.registrationPolicyPendingEmail}</strong> })}</p>
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" onclick={() => this.hide()}>
            {t('close_button')}
          </Button>
        </div>
      </div>
    );
  });

  override(SignUpModal.prototype, 'footer', function (this: any, original: () => unknown) {
    return this.registrationPolicyPendingEmail ? null : original();
  });
});
