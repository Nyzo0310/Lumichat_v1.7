import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener("DOMContentLoaded", function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    const chatContainer = document.querySelector('#chat-messages');

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        input.value = '';

        try {
            const response = await axios.post("/chat", { message });
            const replies = response.data.bot_reply;

            replies.forEach(botMsg => {
                chatContainer.innerHTML += `<div class="self-start"><div class="chat-message">${botMsg}</div></div>`;
            });
        } catch (err) {
            console.error('Error:', err.response || err.message);
        }
    });
});
