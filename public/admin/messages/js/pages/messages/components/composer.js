import { Button } from "/js/components/ui/button.js";

export function Composer() {
  return `
        <form class="amsg-composer is-hidden" id="amsg-form">
          <label class="visually-hidden" for="amsg-input">Message</label>
          <input type="text" id="amsg-input" class="input" placeholder="Write a message&hellip;" autocomplete="off" maxlength="4000">
          ${Button({ text: "Send", variant: "default", icon: "send-horizontal", type: "submit", className: "amsg-send", attrs: 'id="amsg-send"' })}
        </form>`;
}
