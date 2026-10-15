import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Alert from 'flarum/common/components/Alert';
import ForumApplication from 'flarum/forum/ForumApplication';
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

app.initializers.add('jslirola-registration-policy', () => {
  extend(ForumApplication.prototype, 'mount', function () {
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
