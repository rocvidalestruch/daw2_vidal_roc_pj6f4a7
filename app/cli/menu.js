document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("operationsForm");
  const select = document.getElementById("operationSelect");

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const selected = select.value;
    if (selected) {
      window.location.href = selected;
    }
  });
});
