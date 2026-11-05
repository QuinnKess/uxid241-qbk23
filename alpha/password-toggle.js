// Show / hide password button for the login form.
// Written with help from Claude (AI). See README, "AI use".
//
// The button starts hidden in the HTML, so without JavaScript the form
// still works as a normal password field. This only changes what the
// visitor sees; the password is sent and checked the same way either way.

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
  const input = document.getElementById(button.dataset.togglePassword);

  if (!input) {
    return;
  }

  button.hidden = false;

  button.addEventListener('click', () => {
    const is_hidden = input.type === 'password';

    input.type = is_hidden ? 'text' : 'password';
    button.textContent = is_hidden ? 'Hide' : 'Show';
    button.setAttribute('aria-pressed', String(is_hidden));
    input.focus();
  });
});
