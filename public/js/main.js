// public/js/main.js

// Simple fade-out for status messages
document.addEventListener('DOMContentLoaded', () => {
  const messages = document.querySelectorAll('p[style*="color"]');
  messages.forEach(msg => {
    setTimeout(() => {
      msg.style.transition = "opacity 1s";
      msg.style.opacity = "0";
    }, 4000);
  });
});

// Confirm before approving/rejecting requests
function confirmAction(message, url) {
  if (confirm(message)) {
    window.location.href = url;
  }
}
