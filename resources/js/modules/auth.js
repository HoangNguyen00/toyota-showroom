// Local footage is optional; the image remains visible until playback succeeds.
const authVideo = document.querySelector('[data-auth-video]');
if (authVideo) {
    let failed = false;
    authVideo.muted = true;
    const play = () => {
        if (!failed && !document.hidden) authVideo.play().catch(() => {});
    };
    authVideo.addEventListener('playing', () => authVideo.classList.add('is-ready'));
    authVideo.addEventListener('loadeddata', play);
    authVideo.addEventListener('pause', play);
    const fallback = () => { failed = true; authVideo.pause(); authVideo.classList.remove('is-ready'); };
    authVideo.addEventListener('error', fallback);
    authVideo.querySelector('source').addEventListener('error', fallback);
    document.addEventListener('visibilitychange', play);
    window.addEventListener('pageshow', play);
    // A browser may require an initial gesture even for muted video.
    document.addEventListener('pointerdown', play, {once: true});
    document.addEventListener('keydown', play, {once: true});
    play();
}

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    form.querySelectorAll('input[type="password"]').forEach((input) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'auth-password-wrap';
        input.before(wrapper);
        wrapper.append(input);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'auth-password-toggle';
        toggle.textContent = 'Hiện';
        const label = input.labels?.[0]?.textContent.trim() || 'mật khẩu';
        toggle.setAttribute('aria-label', `Hiện ${label.toLowerCase()}`);
        toggle.setAttribute('aria-controls', input.id);
        toggle.setAttribute('aria-pressed', 'false');
        toggle.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Ẩn' : 'Hiện';
            toggle.setAttribute('aria-pressed', String(show));
            toggle.setAttribute('aria-label', `${show ? 'Ẩn' : 'Hiện'} ${label.toLowerCase()}`);
        });
        wrapper.append(toggle);
    });
    const submit = form.querySelector('button[type="submit"]');
    const originalText = submit.textContent;
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        submit.disabled = true;
        submit.textContent = 'Đang xử lý…';
    });
    window.addEventListener('pageshow', () => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        submit.disabled = false;
        submit.textContent = originalText;
        form.querySelectorAll('.auth-password-wrap input').forEach((input) => {
            if (input.type === 'text') input.parentElement.querySelector('button').click();
        });
    });
});
