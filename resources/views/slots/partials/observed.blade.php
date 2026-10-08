    <section class="reality-network" id="reality-network" aria-labelledby="reality-network-title" data-toc-section>
              <header class="reality-network__head">
                <div>
                  <p class="reality-network__eyebrow">{{ __('slot.obs_eyebrow') }}</p>
                  <h2 class="reality-network__title" id="reality-network-title">
                    {{ __('slot.obs_title') }}
                  </h2>
                  <p class="reality-network__intro">
                    {{ __('slot.obs_intro', ['title' => $title]) }}
                  </p>
                </div>
              </header>

              <aside class="reality-network__notice" aria-label="{{ __('slot.obs_quality_title') }}">
                <div class="tracker-provenance">
                  <p><strong>{{ __('slot.obs_quality_title') }}</strong> — {{ __('slot.obs_quality_body') }}</p>
                  <dl>
                    <div><dt>{{ __('slot.obs_dataset_status') }}</dt><dd>{{ __('slot.obs_dataset_value') }}</dd></div>
                    <div><dt>{{ __('slot.obs_game_id') }}</dt><dd>PP-GOO-6x5-v3.2.1</dd></div>
                    <div><dt>{{ __('slot.obs_build_rtp') }}</dt><dd>{{ __('slot.obs_build_value', ['rtp' => $rtpLabel]) }}</dd></div>
                    <div><dt>{{ __('slot.obs_jurisdiction') }}</dt><dd>United Kingdom · MGA/UKGC routes</dd></div>
                    <div><dt>{{ __('slot.obs_source') }}</dt><dd>Opted-in collectors · captured 5 Sep 2026, 08:40 UTC</dd></div>
                    <div><dt>{{ __('slot.obs_verification') }}</dt><dd>Verified · cohort above minimum threshold</dd></div>
                    <div><dt>{{ __('slot.obs_methodology_ver') }}</dt><dd>Methodology v2.4 · rolling sample windows</dd></div>
                    <div><dt>{{ __('slot.obs_complete_rule') }}</dt><dd>Applied · start/end, currency, wager, return and config matched</dd></div>
                  </dl>
                  <p>{{ __('slot.obs_coverage') }}</p>
                </div>
              </aside>

              <div class="reality-network__notice">
                <span class="reality-network__notice-icon" aria-hidden="true">i</span>
                <p>
                  <strong>{{ __('slot.obs_how_to_read') }}</strong> {{ __('slot.obs_how_to_read_body') }}
                </p>
              </div>

              <div class="community-tracker__controls">
                <div class="community-tracker__status">
                  <span class="reality-livebar__pulse" aria-hidden="true"></span>
                  <div>
                    <strong id="tracker-window-label">{{ __('slot.obs_live_window', ['window' => __('slot.obs_window_24h')]) }}</strong>
                    <span id="tracker-window-players">{{ __('slot.obs_players_line', ['count' => '1,284']) }}</span>
                  </div>
                </div>
                <div class="community-tracker__selectors">
                  <label>
                    <span>{{ __('slot.obs_source_filter') }}</span>
                    <select id="tracker-casino" aria-label="{{ __('slot.obs_filter_casino') }}">
                      <option value="all" selected>{{ __('slot.obs_all_casinos') }}</option>
                      <option value="stake">Stake</option>
                      <option value="leovegas">LeoVegas</option>
                      <option value="bet365">Bet365</option>
                      <option value="casumo">Casumo</option>
                    </select>
                  </label>
                  <label>
                    <span>{{ __('slot.obs_mode_filter') }}</span>
                    <select id="tracker-mode" aria-label="{{ __('slot.obs_filter_mode') }}">
                      <option value="all" selected>{{ __('slot.obs_all_modes') }}</option>
                      <option value="base">{{ __('slot.obs_normal_spins') }}</option>
                      <option value="bonus">{{ __('slot.obs_bonus_buy') }}</option>
                      <option value="demo">{{ __('slot.demo_play') }}</option>
                    </select>
                  </label>
                  <div class="community-tracker__ranges" role="group" aria-label="{{ __('slot.obs_choose_window') }}">
                    <button type="button" data-live-range="1h" aria-pressed="false">{{ __('slot.obs_window_1h') }}</button>
                    <button class="is-active" type="button" data-live-range="24h" aria-pressed="true">{{ __('slot.obs_window_24h') }}</button>
                    <button type="button" data-live-range="7d" aria-pressed="false">{{ __('slot.obs_window_7d') }}</button>
                  </div>
                </div>
              </div>

              <div class="community-kpis" aria-live="polite">
                <article>
                  <span>{{ __('slot.obs_tracked_spins') }}</span>
                  <strong id="tracker-spins">186,420</strong>
                  <small>{{ __('slot.obs_complete_events') }}</small>
                </article>
                <article>
                  <span>{{ __('slot.obs_tracked_players') }}</span>
                  <strong id="tracker-players">1,284</strong>
                  <small>{{ __('slot.obs_opted_in_players') }}</small>
                </article>
                <article>
                  <span>{{ __('slot.obs_total_wagered') }}</span>
                  <strong id="tracker-wagered">$284,610</strong>
                  <small>{{ __('slot.obs_usd_sessions') }}</small>
                </article>
                <article>
                  <span>{{ __('slot.obs_returned') }}</span>
                  <strong id="tracker-returned">$266,190</strong>
                  <small>{{ __('slot.obs_paid_back') }}</small>
                </article>
                <article class="community-kpis__net is-negative">
                  <span>{{ __('slot.obs_net_result') }}</span>
                  <strong id="tracker-net">−$18,420</strong>
                  <small>{{ __('slot.obs_return_minus') }}</small>
                </article>
                <article>
                  <span>Observed return</span>
                  <strong id="tracker-return-rate">93.53%</strong>
                  <small>sample return · not game RTP</small>
                </article>
              </div>

              <section class="community-chart" aria-labelledby="community-chart-title">
                <div class="community-chart__head">
                  <div>
                    <p>Net result over time</p>
                    <h3 id="community-chart-title">Returns minus wagers — Last 24 hours</h3>
                  </div>
                  <span id="tracker-updated">Updated 5 min ago · window open</span>
                </div>

                <div class="community-chart__stats">
                  <div class="community-chart__stat-grid">
                    <article>
                      <span>Spins observed</span>
                      <strong id="tracker-pulse-spins">186,420</strong>
                    </article>
                    <article>
                      <span>No-return run</span>
                      <strong id="tracker-pulse-dry">19 spins</strong>
                    </article>
                    <article>
                      <span>Largest return</span>
                      <strong id="tracker-pulse-largest">742× stake</strong>
                    </article>
                    <article>
                      <span>Contributors</span>
                      <strong id="tracker-pulse-users">1,284</strong>
                    </article>
                  </div>
                  <div class="community-chart__net is-negative" id="tracker-pulse-card">
                    <span>Tracked players’ net</span>
                    <strong id="tracker-pulse-net">−$18,420</strong>
                    <small id="tracker-pulse-label">More was wagered than returned</small>
                  </div>
                </div>

                <div class="community-chart__toolbar">
                  <p class="community-chart__hint">
                    See when players came out ahead or fell behind across the selected window.
                    Hover or tap any point to explore the session data behind it.
                  </p>
                  <div class="community-chart__tabs" role="group" aria-label="Chart time window">
                    <button type="button" data-chart-range="1h" aria-pressed="false">{{ __('slot.obs_window_1h') }}</button>
                    <button type="button" data-chart-range="6h" aria-pressed="false">6 hours</button>
                    <button class="is-active" type="button" data-chart-range="24h" aria-pressed="true">{{ __('slot.obs_window_24h') }}</button>
                  </div>
                </div>

                <div class="community-chart__plot" id="community-chart-plot" role="img" aria-label="Hourly net-result chart"></div>
                <div class="community-chart__axis" id="community-chart-axis" aria-hidden="true">
                  <span>Window start</span><span>03:00</span><span>06:00</span><span>09:00</span><span>12:00</span><span>15:00</span><span>18:00</span><span>21:00</span><span>Window end</span>
                </div>
                <div class="community-chart__legend">
                  <span><i class="is-positive"></i>Players returned more than wagered</span>
                  <span><i class="is-negative"></i>Players lost more than returned</span>
                </div>
                <p class="community-chart__formula">
                  Each bar = total returns minus total wagers for that interval.
                  Hover or tap a bar to see the sample amount.
                </p>
              </section>

              <section class="watchable-dna" aria-labelledby="watchable-dna-title">
                <div class="watchable-dna__head">
                  <div>
                    <p>Outcome breakdown</p>
                    <h3 id="watchable-dna-title">How tracked spins ended</h3>
                  </div>
                  <span>All sources and modes for the selected window</span>
                </div>
                <div class="tracker-analysis__grid">
                  <article class="outcome-breakdown">
                    <div class="outcome-breakdown__row">
                      <div><span>No return</span><strong id="outcome-zero-value">61.2%</strong></div>
                      <div class="outcome-breakdown__track"><i class="is-zero" id="outcome-zero-bar" style="width: 61.2%"></i></div>
                      <small>Returned $0</small>
                    </div>
                    <div class="outcome-breakdown__row">
                      <div><span>Return ≤ stake</span><strong id="outcome-under-value">18.4%</strong></div>
                      <div class="outcome-breakdown__track"><i class="is-under" id="outcome-under-bar" style="width: 18.4%"></i></div>
                      <small>Positive return, but no net profit</small>
                    </div>
                    <div class="outcome-breakdown__row">
                      <div><span>1×–10× stake</span><strong id="outcome-small-value">17.1%</strong></div>
                      <div class="outcome-breakdown__track"><i class="is-win" id="outcome-small-bar" style="width: 17.1%"></i></div>
                      <small>Net-positive spins</small>
                    </div>
                    <div class="outcome-breakdown__row">
                      <div><span>10×–100× stake</span><strong id="outcome-mid-value">3.0%</strong></div>
                      <div class="outcome-breakdown__track"><i class="is-win" id="outcome-mid-bar" style="width: 3%"></i></div>
                      <small>Higher observed returns</small>
                    </div>
                    <div class="outcome-breakdown__row">
                      <div><span>100×+ stake</span><strong id="outcome-high-value">0.3%</strong></div>
                      <div class="outcome-breakdown__track"><i class="is-bonus" id="outcome-high-bar" style="width: 0.3%"></i></div>
                      <small>Rare in this sample window</small>
                    </div>
                  </article>

                  <article class="tracker-explainer">
                    <span class="tracker-explainer__label">Collection pipeline</span>
                    <h4>How opted-in session data is handled</h4>
                    <ol>
                      <li><b>1</b><span>Opt-in collection documents supported session sources and consent.</span></li>
                      <li><b>2</b><span>Personal details are removed before normalization and storage.</span></li>
                      <li><b>3</b><span>Only verified matching deployments enter the delayed aggregate.</span></li>
                    </ol>
                    <a href="{{ $guidesUrl ?? '/guides/' }}">How collection and verification work</a>
                  </article>
                </div>
              </section>

              <section class="reality-timeline" aria-labelledby="reality-timeline-title">
                <div class="reality-timeline__head">
                  <div>
                    <p>Recent session events</p>
                    <h3 id="reality-timeline-title">Latest tracked session rows</h3>
                  </div>
                  <span class="tracker-feed__scope" id="tracker-feed-scope">All sources · all modes</span>
                </div>

                <div class="tracker-feed-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Time</th>
                        <th>Source</th>
                        <th>Mode</th>
                        <th>Bet</th>
                        <th>Returned</th>
                        <th>Result</th>
                      </tr>
                    </thead>
                    <tbody id="tracker-feed-body">
                      <tr data-feed-casino="stake" data-feed-mode="base">
                        <td>Window start</td>
                        <td>Stake</td>
                        <td>Normal spin</td>
                        <td>$1.00</td>
                        <td>$0.00</td>
                        <td><strong class="is-negative">−$1.00</strong></td>
                      </tr>
                      <tr data-feed-casino="leovegas" data-feed-mode="base">
                        <td>+4 min</td>
                        <td>LeoVegas</td>
                        <td>Normal spin</td>
                        <td>$0.40</td>
                        <td>$3.24</td>
                        <td><strong class="is-positive">+$2.84</strong></td>
                      </tr>
                      <tr data-feed-casino="bet365" data-feed-mode="bonus">
                        <td>+7 min</td>
                        <td>Bet365</td>
                        <td>Bonus buy</td>
                        <td>$20.00</td>
                        <td>$8.60</td>
                        <td><strong class="is-negative">−$11.40</strong></td>
                      </tr>
                      <tr data-feed-casino="stake" data-feed-mode="base">
                        <td>+11 min</td>
                        <td>Stake</td>
                        <td>Normal spin</td>
                        <td>$0.80</td>
                        <td>$0.40</td>
                        <td><strong class="is-negative">−$0.40</strong></td>
                      </tr>
                      <tr data-feed-casino="leovegas" data-feed-mode="bonus">
                        <td>+16 min</td>
                        <td>LeoVegas</td>
                        <td>Bonus buy</td>
                        <td>$50.00</td>
                        <td>$186.20</td>
                        <td><strong class="is-positive">+$136.20</strong></td>
                      </tr>
                      <tr data-feed-casino="bet365" data-feed-mode="base">
                        <td>+21 min</td>
                        <td>Bet365</td>
                        <td>Normal spin</td>
                        <td>$0.20</td>
                        <td>$0.00</td>
                        <td><strong class="is-negative">−$0.20</strong></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="tracker-feed__note">
                  Values are delayed, anonymized and deduplicated. Sessions are grouped only when the game configuration can be matched.
                </p>
              </section>

              <section class="deployment-watch" aria-labelledby="deployment-watch-title">
                <div class="deployment-watch__head">
                  <div>
                    <p>Coverage by casino</p>
                    <h3 id="deployment-watch-title">Coverage by casino</h3>
                  </div>
                  <a href="#slot-rtp">Why configurations stay separate</a>
                </div>
                <div class="casino-results-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Source</th>
                        <th>Spins</th>
                        <th>Players</th>
                        <th>Wagered</th>
                        <th>Returned</th>
                        <th>Players’ net</th>
                        <th>Observed return</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr data-casino-row="stake">
                        <td><strong>Stake</strong><small>96.50% RTP · verified build</small></td>
                        <td>84,210</td><td>518</td><td>$126,380</td><td>$120,410</td>
                        <td class="is-negative">−$5,970</td><td>95.28%</td>
                      </tr>
                      <tr data-casino-row="leovegas">
                        <td><strong>LeoVegas</strong><small>96.50% RTP · verified build</small></td>
                        <td>61,840</td><td>439</td><td>$92,540</td><td>$84,920</td>
                        <td class="is-negative">−$7,620</td><td>91.77%</td>
                      </tr>
                      <tr data-casino-row="bet365">
                        <td><strong>Bet365</strong><small>96.47% RTP · verified build</small></td>
                        <td>40,370</td><td>327</td><td>$65,690</td><td>$60,860</td>
                        <td class="is-negative">−$4,830</td><td>92.65%</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="casino-results-table__note">
                  Totals are observed session aggregates, not theoretical RTP. Verified game and paytable configurations are kept separate by source.
                </p>
              </section>

              <footer class="reality-network__foot">
                <p>
                  <strong>In plain English:</strong> this is a delayed community aggregate, not a personal tracker. It summarizes opted-in sessions and does not predict future outcomes.
                </p>
                <a href="{{ $guidesUrl ?? '/guides/' }}">Read the tracking methodology</a>
              </footer>
            </section>
