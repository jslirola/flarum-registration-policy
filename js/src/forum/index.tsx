import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Alert from 'flarum/common/components/Alert';
import Page from 'flarum/common/components/Page';
import Notices from 'flarum/forum/components/Notices';
import PendingConfirmationModal from './components/PendingConfirmationModal';

/** Must match RegisterController::META_KEY. */
const META_KEY = 'jslirolaRegistrationPolicy';

/**
 * The address a confirmation email was just sent to, handed over by the server
 * on the page that loads right after the sign-up (PendingConfirmationNotice).
 *
 * The sign-up modal itself is left alone: core reloads the page after a
 * successful POST /register, and other extensions (fof/recaptcha) wrap its
 * submit too. Telling the visitor on the next page works whoever submitted.
 */
function pendingEmail(): string | null {
  if (app.session.user) return null;

  const email = (app.data as any)?.[META_KEY]?.pendingEmail;

  return typeof email === 'string' && email !== '' ? email : null;
}

let modalShown = false;

app.initializers.add('jslirola-registration-policy', () => {
  // Not on mount: every Page closes the open modal in its oninit, and the first
  // page is created right after mount — the dialog would vanish before it was
  // drawn, and anything waiting for an empty modal slot (an ad-blocker notice,
  // say) would take its place. Once the first page exists, nothing closes it.
  extend(Page.prototype, 'oncreate', function () {
    if (modalShown) return;
    modalShown = true;

    const email = pendingEmail();

    if (email) app.modal.show(PendingConfirmationModal, { email });
  });

  extend(Notices.prototype, 'items', function (items) {
    const email = pendingEmail();

    if (!email) return;

    items.add(
      'registrationPolicyPendingConfirmation',
      <Alert dismissible={false} className="Alert--emailConfirmation" containerClassName="container">
        {app.translator.trans('jslirola-registration-policy.forum.pending_confirmation.notice', { email: <strong>{email}</strong> })}
      </Alert>,
      100
    );
  });
});
