<div>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 120" width="100%" height="100%">
        <defs>
            <linearGradient id="nexusGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#0ea5e9" />
            <stop offset="100%" stop-color="#4f46e5" />
            </linearGradient>
            <linearGradient id="nexusGradientLight" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#38bdf8" />
            <stop offset="100%" stop-color="#818cf8" />
            </linearGradient>
        </defs>

        <!-- Logo Mark (Nexus + Isometric Book/Thesis) -->
        <g transform="translate(25, 20)">
            <!-- Left Book Page -->
            <path d="M40 75 L10 60 L10 30 L40 45 Z" fill="url(#nexusGradient)" opacity="0.95"/>

            <!-- Right Book Page -->
            <path d="M40 75 L70 60 L70 30 L40 45 Z" fill="url(#nexusGradient)" opacity="0.75"/>

            <!-- Nexus / Network Cap -->
            <path d="M40 15 L70 30 L40 45 L10 30 Z" fill="none" stroke="url(#nexusGradientLight)" stroke-width="3" stroke-linejoin="round"/>
            <line x1="40" y1="15" x2="40" y2="45" stroke="url(#nexusGradientLight)" stroke-width="3"/>

            <!-- Central and Peripheral Nodes -->
            <circle cx="40" cy="15" r="4.5" fill="#1e1b4b" />
            <circle cx="10" cy="30" r="4.5" fill="#1e1b4b" />
            <circle cx="70" cy="30" r="4.5" fill="#1e1b4b" />

            <!-- Glowing Central Core -->
            <circle cx="40" cy="45" r="5.5" fill="#ffffff" stroke="url(#nexusGradient)" stroke-width="2.5"/>

            <!-- Bottom Document Nodes -->
            <circle cx="10" cy="60" r="3" fill="#ffffff" opacity="0.8" />
            <circle cx="70" cy="60" r="3" fill="#ffffff" opacity="0.8" />
            <circle cx="40" cy="75" r="4" fill="#ffffff" />
        </g>

        <!-- Typography -->
        <text x="115" y="66" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-size="42" letter-spacing="-0.75">
            <tspan font-weight="800" fill="#0f172a">nexus</tspan><tspan font-weight="300" fill="#334155">Thesis</tspan>
        </text>

        <!-- Subtitle / Tagline -->
        <text x="118" y="88" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-size="11" font-weight="600" fill="#64748b" letter-spacing="2.5">
            RESEARCH MANAGEMENT PLATFORM
        </text>
    </svg>
</div>
