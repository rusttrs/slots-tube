<div
      class="auth-modal"
      id="auth-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="auth-modal-title"
      hidden
    >
      <div class="auth-modal__backdrop js-close-auth" tabindex="-1"></div>
      <div class="auth-modal__dialog">
        <button
          class="auth-modal__close js-close-auth"
          type="button"
          aria-label="Close"
        >
          <x-site-icon name="modal-close" width="30" height="30" />
        </button>

        <p class="auth-modal__error" id="auth-form-error" role="alert" hidden style="margin:0 0 12px;"></p>
        <div class="auth-modal__panel" data-auth-panel="login">
          <div class="auth-modal__logo" aria-hidden="true">
            <img src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
            <span>slots.tube</span>
          </div>
          <h2 class="visually-hidden" id="auth-modal-title">Log in</h2>
          <form class="auth-modal__form" id="auth-login-form" action="/auth/magic-link" method="post" novalidate data-auth-intent="login">
            <label class="auth-modal__field">
              <span class="visually-hidden">Email address</span>
              <input
                class="auth-modal__input"
                type="email"
                name="email"
                id="auth-login-email"
                placeholder="Email address..."
                autocomplete="email"
                required
              />
            </label>
            <button class="auth-modal__cta" type="submit">Log in</button>
          </form>
          <p class="auth-modal__switch">
            Don’t have an account?
            <button class="auth-modal__link js-auth-panel" type="button" data-auth-target="signup">Sign up</button>
          </p>
          <div class="auth-modal__or" aria-hidden="true">
            <span></span>
            <em>or</em>
            <span></span>
          </div>
          <a class="auth-modal__soc" href="/auth/google">
            <x-site-icon name="google" width="20" height="20" />
            <span>Log in with Google</span>
          </a>
        </div>

        <div class="auth-modal__panel" data-auth-panel="signup" hidden>
          <div class="auth-modal__logo" aria-hidden="true">
            <img src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
            <span>slots.tube</span>
          </div>
          <form class="auth-modal__form" id="auth-signup-form" action="/auth/magic-link" method="post" novalidate data-auth-intent="signup">
            <label class="auth-modal__age">
              <input class="auth-modal__checkbox" type="checkbox" name="age" id="auth-age" value="1" />
              <span>
                I confirm i’m over 18 years old and that i have read
                <a href="{{ localized_url(null, 'terms') }}">Terms and conditions</a>
              </span>
            </label>
            <label class="auth-modal__field">
              <span class="visually-hidden">Email address</span>
              <input
                class="auth-modal__input"
                type="email"
                name="email"
                id="auth-signup-email"
                placeholder="Email address..."
                autocomplete="email"
                required
              />
            </label>
            <p class="auth-modal__error" id="auth-age-error" role="alert" hidden>
              Please confirm that you are over 18 years old!
            </p>
            <button class="auth-modal__cta" type="submit">Sign up</button>
          </form>
          <p class="auth-modal__switch">
            Already have an account?
            <button class="auth-modal__link js-auth-panel" type="button" data-auth-target="login">Log in</button>
          </p>
          <div class="auth-modal__or" aria-hidden="true">
            <span></span>
            <em>or</em>
            <span></span>
          </div>
          <a class="auth-modal__soc" href="/auth/google">
            <x-site-icon name="google" width="20" height="20" />
            <span>Sign up with Google</span>
          </a>
        </div>

        <div class="auth-modal__panel" data-auth-panel="check-email" hidden>
          <div class="auth-modal__logo" aria-hidden="true">
            <img src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
            <span>slots.tube</span>
          </div>
          <p class="auth-modal__check-title">Check your email folder</p>
          <p class="auth-modal__check-text">
            We have sent you a magic link at<br />
            <strong id="auth-check-email">superguy@gmail.com</strong>
          </p>
          <hr class="auth-modal__divider" />
          <p class="auth-modal__switch">
            Incorrect email?
            <button class="auth-modal__link js-auth-panel" type="button" data-auth-target="login">Change email</button>
          </p>
          <p class="auth-modal__switch">
            Didn’t get it?
            <button class="auth-modal__link" type="button" id="auth-resend">Resend</button>
          </p>
        </div>
      </div>
    </div>
