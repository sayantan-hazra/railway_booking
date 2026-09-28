// <------ SAYANTAN PAL ------>

/* =========================================================
   RAIL EASE - USER HOMEPAGE JAVASCRIPT
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
  /*
   * ==========================================
   * JOURNEY DATE
   * ==========================================
   *
   * Prevent selecting a date before today.
   */

  const journeyDate = document.getElementById("journey_date");

  if (journeyDate) {
    const today = new Date();

    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");

    const formattedToday = `${year}-${month}-${day}`;

    journeyDate.min = formattedToday;
  }

  /*
   * ==========================================
   * SWAP FROM / TO STATIONS
   * ==========================================
   */

  const swapButton = document.getElementById("swapStations");

  const fromInput = document.getElementById("from");
  const toInput = document.getElementById("to");

  if (swapButton && fromInput && toInput) {
    swapButton.addEventListener("click", function () {
      const temporaryValue = fromInput.value;

      fromInput.value = toInput.value;
      toInput.value = temporaryValue;
    });
  }

  /*
   * ==========================================
   * TRAIN SEARCH VALIDATION
   * ==========================================
   */

  const searchForm = document.getElementById("trainSearchForm");

  if (searchForm) {
    searchForm.addEventListener("submit", function (event) {
      const fromValue = fromInput.value.trim();
      const toValue = toInput.value.trim();

      if (fromValue === "" || toValue === "") {
        return;
      }

      /*
       * Prevent searching the same station.
       */

      if (fromValue.toLowerCase() === toValue.toLowerCase()) {
        event.preventDefault();

        alert("Departure and destination stations cannot be the same.");

        return;
      }
    });
  }
});
