document.addEventListener("DOMContentLoaded", function () {
  const printBtn = document.getElementById("print-button");
  if (!printBtn) return;

  printBtn.addEventListener("click", function (e) {
    e.preventDefault();

    fetch(PrintLimitAjax.ajax_url + "?action=handle_print", {
      credentials: "same-origin",
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          window.print();
        } else {
          alert(data.data); // Show limit reached message
        }
      });
  });
});
