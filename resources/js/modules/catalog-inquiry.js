const dialog = document.querySelector('[data-catalog-inquiry]');
if (dialog) {
    const form = dialog.querySelector('[data-catalog-inquiry-form]');
    const feedback = form?.querySelector('[data-catalog-inquiry-feedback]');
    // Ủy quyền sự kiện để cả thẻ xe chèn sau khi tải (trang Xe quan tâm) cũng mở được dialog.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-open-catalog-inquiry]');
        if (!button) return;
        if (form) {
            form.elements.car_id.value = button.dataset.carId || '';
            feedback.hidden = true;
        }
        dialog.querySelector('[data-catalog-car-name]').textContent = button.dataset.carName || 'Showroom sẽ liên hệ với bạn để hỗ trợ.';
        dialog.showModal();
    });
    let busy = false;
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Đang gửi…';
        feedback.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}});
            const body = await response.json().catch(() => { throw new Error('Máy chủ chưa phản hồi đúng. Vui lòng thử lại.'); });
            if (response.status !== 201 || !Number.isInteger(body.id)) {
                throw new Error(body.errors ? Object.values(body.errors).flat().join(' ') : body.message || 'Không thể gửi yêu cầu.');
            }
            feedback.textContent = `${body.message} Mã tham chiếu: #${body.id}.`;
            feedback.classList.add('is-success');
            if (form.dataset.chatUrl) {
                const link = document.createElement('a');
                const target = new URL(form.dataset.chatUrl, location.origin);
                target.searchParams.set('conversation', body.id);
                link.href = target;
                link.textContent = ' Xem cuộc tư vấn';
                feedback.append(link);
            }
            form.elements.content.value = '';
        } catch (error) {
            feedback.classList.remove('is-success');
            feedback.textContent = `Chưa gửi được: ${error instanceof TypeError ? 'Không thể kết nối tới showroom. Vui lòng thử lại.' : error.message}`;
        } finally {
            feedback.hidden = false;
            busy = false;
            submit.disabled = false;
            submit.textContent = 'Gửi yêu cầu tư vấn';
        }
    });
}
