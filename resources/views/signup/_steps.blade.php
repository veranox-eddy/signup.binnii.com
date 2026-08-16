{{-- Two steps only — the first Center is created after sign-in
     (spec §2.1); the wireframe's "3 · First center" is intentionally gone. --}}
<p class="steps">
    <span class="{{ $current === 1 ? 'on' : 'off' }}">1 · Account</span>
    <span class="{{ $current === 2 ? 'on' : 'off' }}">2 · Organization</span>
</p>
