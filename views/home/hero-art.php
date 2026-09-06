              <svg viewBox="0 0 440 380" class="hero-art" role="img" aria-label="Map locating rescued Puspin and Aspin">
                <defs>
                  <linearGradient id="pinGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" class="hero-art-pin-stop-a" />
                    <stop offset="1" class="hero-art-pin-stop-b" />
                  </linearGradient>
                  <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
                    <circle cx="3" cy="3" r="2.2" class="hero-art-dot" />
                  </pattern>
                </defs>
                <rect x="0" y="0" width="440" height="380" fill="url(#dots)" opacity="0.55" />
                <path d="M80 300 C 150 230 190 250 225 160 S 340 130 380 90"
                      fill="none" class="hero-art-route"
                      stroke-width="3" stroke-dasharray="5 9" stroke-linecap="round" />
                <circle cx="80" cy="300" r="10" class="hero-art-node" stroke-width="3" />
                <circle cx="380" cy="90" r="10" class="hero-art-node" stroke-width="3" />
                <circle cx="120" cy="110" r="40" class="hero-art-halo hero-art-halo--a" />
                <circle cx="330" cy="270" r="30" class="hero-art-halo hero-art-halo--b" />
                <g transform="translate(225 175)">
                  <ellipse cx="0" cy="22" rx="26" ry="8" class="hero-art-shadow" />
                  <path d="M0 14 C -34 -36 -34 -82 0 -82 C 34 -82 34 -36 0 14 Z" fill="url(#pinGrad)" />
                  <g class="hero-art-paw" transform="translate(0 -52)">
                    <ellipse cx="0" cy="8" rx="14" ry="11" />
                    <circle cx="-14" cy="-6" r="5.5" />
                    <circle cx="14" cy="-6" r="5.5" />
                    <circle cx="-6" cy="-14" r="5" />
                    <circle cx="6" cy="-14" r="5" />
                  </g>
                </g>
                <g class="hero-art-heart">
                  <path transform="translate(300 150) scale(1.1)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
                  <path transform="translate(150 250) scale(0.9)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
                </g>
              </svg>
