<footer class="footer">
  <div class="container footer__bar">
    <hr class="footer__rule" aria-hidden="true" />

    <div class="footer__blocks">
      <div class="footer__col footer__col--left">
        <div class="footer__card footer__card--trust">
          <div class="footer__trust-top">
            <span class="footer__badge">18+</span>
            <p class="footer__review-label">Review us on</p>
            <div class="footer__trustpilot" aria-label="Trustpilot">
              <div class="footer__trustpilot-stars">
                <img src="/assets/images/footer/5b853.svg" alt="" width="23" height="23" />
                <img src="/assets/images/footer/5b853.svg" alt="" width="23" height="23" />
                <img src="/assets/images/footer/9f2b3.svg" alt="" width="22" height="23" />
                <img src="/assets/images/footer/5b853.svg" alt="" width="23" height="23" />
                <img src="/assets/images/footer/9ed60.svg" alt="" width="23" height="23" />
              </div>
              <img
                class="footer__trustpilot-logo"
                src="/assets/images/footer/e012d.svg"
                alt="Trustpilot"
                width="93"
                height="20"
              />
            </div>
          </div>
          <div class="footer__trust-logos">
            <a
              class="footer__trust-logo footer__trust-logo--gamcare"
              href="https://www.gamcare.org.uk/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="GamCare"
            >
              <img src="/assets/images/footer/e49f3.svg" alt="GamCare" />
            </a>
            <a
              class="footer__trust-logo footer__trust-logo--bga"
              href="https://www.begambleaware.org/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="BeGambleAware"
            >
              <img src="/assets/images/footer/5b7de.svg" alt="BeGambleAware" />
            </a>
            <a
              class="footer__trust-logo footer__trust-logo--gamstop"
              href="https://www.gamstop.co.uk/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="GamStop"
            >
              <img src="/assets/images/footer/29437.svg" alt="GamStop" />
            </a>
          </div>
        </div>

        <div class="footer__card footer__card--social">
          <a class="footer__soc" href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer">
            <img class="footer__soc-icon" src="/assets/images/footer/50c54.svg" alt="" width="40" height="40" />
            <span>YouTube</span>
          </a>
          <a class="footer__soc" href="https://twitter.com/" target="_blank" rel="noopener noreferrer">
            <img class="footer__soc-icon" src="/assets/images/footer/ea06b.svg" alt="" width="40" height="40" />
            <span>Twitter</span>
          </a>
          <a class="footer__soc" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">
            <img class="footer__soc-icon" src="/assets/images/footer/0e04e.svg" alt="" width="40" height="40" />
            <span>Instagram</span>
          </a>
          <a class="footer__soc" href="https://t.me/" target="_blank" rel="noopener noreferrer">
            <img class="footer__soc-icon" src="/assets/images/footer/ae28f.svg" alt="" width="40" height="40" />
            <span>Telegram</span>
          </a>
        </div>
      </div>

      <div class="footer__card footer__card--newsletter">
        <h2 class="footer__newsletter-title">Get free slots news &amp; bonuses</h2>
        <form class="footer__form" id="newsletter-form" action="{{ url('/newsletter/subscribe') }}" method="post" novalidate>
          @csrf
          <div class="footer__checks">
            <label class="footer__check">
              <input type="checkbox" name="age" value="1" checked required />
              <span class="footer__check-box" aria-hidden="true"></span>
              <span class="footer__check-text">I am over 18</span>
            </label>
            <label class="footer__check">
              <input type="checkbox" name="newsletter" value="1" checked required />
              <span class="footer__check-box" aria-hidden="true"></span>
              <span class="footer__check-text">I want to receive the newsletter</span>
            </label>
          </div>
          <div class="footer__input-wrap">
            <input
              class="footer__input"
              type="email"
              name="email"
              id="newsletter-email"
              placeholder="Email address..."
              autocomplete="email"
              required
            />
            <button class="btn btn--subscribe" type="submit">Subscribe</button>
          </div>
          <p class="footer__form-message" id="newsletter-message" role="status" aria-live="polite" @if(!session('newsletter_success')) hidden @endif>
            {{ session('newsletter_success') }}
          </p>
          <p class="footer__disclaimer">
            By subscribing you agree to our
            <a href="{{ localized_url(null, 'privacy') }}">Privacy Policy</a>.
          </p>
        </form>
      </div>
    </div>

    <hr class="footer__rule" />

    <nav class="footer__sitemap" aria-label="Sitemap">
      <div class="footer__sitemap-col">
        <p class="footer__sitemap-title">Slots</p>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'free-slots') }}">Free Slots</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'crash-games') }}">Crash Games</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'other-games') }}">Other Games</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'providers') }}">Providers</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'by-feature') }}">By Feature</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'by-themes') }}">By Themes</a>
      </div>
      <div class="footer__sitemap-col">
        <p class="footer__sitemap-title">Streamers</p>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'streamers') }}">Streamers</a>
      </div>
      <div class="footer__sitemap-col">
        <p class="footer__sitemap-title">News</p>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'news') }}">News</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'guides') }}">Guides</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'blogs') }}">Blogs</a>
      </div>
      <div class="footer__sitemap-col">
        <p class="footer__sitemap-title">About Us</p>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'authors') }}">Our Team</a>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'our-mission') }}">Our Mission</a>
      </div>
      <div class="footer__sitemap-col">
        <p class="footer__sitemap-title">Rewards</p>
        <a class="footer__sitemap-link" href="{{ localized_url(null, 'bonuses') }}">Bonuses</a>
      </div>
    </nav>

    <div class="footer__bottom">
      <p class="footer__copy">© 2025 slots.tube</p>
      <nav class="footer__legal" aria-label="Legal">
        <a href="{{ localized_url(null, 'privacy') }}">Privacy Policy</a>
        <a href="{{ localized_url(null, 'terms') }}">Terms And Conditions</a>
        <a href="{{ localized_url(null, 'cookies') }}">Cookie Policy</a>
        <a href="{{ localized_url(null, 'responsible-gaming') }}">Responsible Gaming</a>
      </nav>
    </div>
  </div>
</footer>
