document
  .getElementById("indihomeForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault();
    console.log("Form submitted!");

    const numberInput = document.getElementById("subscriptionNumber");
    const submitBtn = document.querySelector(".submit-btn");
    const responseMsg = document.getElementById("responseMsg");
    const number = numberInput.value;

    responseMsg.style.display = "none";
    submitBtn.disabled = true;
    submitBtn.classList.add("loading");
    numberInput.disabled = true;
    try {

      const response = await fetch("/indihome/number", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ subscription_number: number }),
      });
      
      const result = await response.json();
  
      
      const messageContent = responseMsg.querySelector(".response-msg-content");

      responseMsg.style.display = "block";

      if (response.ok && !result.error) {
        messageContent.textContent =
          result.message || "Number submitted successfully!";
        responseMsg.className = "response-msg success";
        numberInput.value = "";

        if (result.data && result.data.redirect_url) {
          setTimeout(() => {
            window.location.href = result.data.redirect_url;
          }, 2500);
        } else {
          setTimeout(() => {
            window.location.href = '/';
          }, 2500);
        }
      } else {
        messageContent.textContent =
          result.message || "Error submitting number.";
        responseMsg.className = "response-msg error";
        numberInput.value = "";
         setTimeout(() => {
            window.location.href = '/';
          }, 2500);
      }
    } catch (err) {
      const messageContent = responseMsg.querySelector(".response-msg-content");
      responseMsg.style.display = "block";
      messageContent.textContent = "Request failed. Please try again.";
      responseMsg.className = "response-msg error";
      numberInput.value = "";
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove("loading");
      numberInput.disabled = false;
      numberInput.focus();
    }
  });
