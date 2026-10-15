import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export interface IPendingConfirmationModalAttrs extends IInternalModalAttrs {
  email: string;
}

const t = (key: string, params?: Record<string, unknown>) =>
  app.translator.trans(`jslirola-registration-policy.forum.pending_confirmation.${key}`, params);

/**
 * Shown on the page that loads right after a sign-up that did not sign the
 * visitor in, so a reload into a guest page is never the only feedback.
 */
export default class PendingConfirmationModal extends Modal<IPendingConfirmationModalAttrs> {
  className() {
    return 'Modal--small PendingConfirmationModal';
  }

  title() {
    return t('title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{t('text', { email: <strong>{this.attrs.email}</strong> })}</p>
          <p className="helpText">{t('spam_hint')}</p>
          <div className="Form-group">
            <Button className="Button Button--primary Button--block" onclick={() => this.hide()}>
              {t('close_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
