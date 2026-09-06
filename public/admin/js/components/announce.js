import { openDialog } from "/shared/components/dialog/dialog.js";
import { Button } from "/shared/components/button/button.js";
import { Select, initSelect, getSelectValue } from "/shared/components/select/select.js";
import { toast } from "/shared/components/toast/toast.js";
import { broadcastAnnouncement, fetchUnreadCount } from "/assets/js/admin/admin-data.js";
import { setNavBadge } from "/assets/js/lib/swr.js";

const TARGETS = [
  { value: "role:admin", label: "Admins" },
  { value: "role:rescuer", label: "Rescuers" },
  { value: "role:resident", label: "Residents" },
  { value: "all", label: "All users" },
];

export function AnnounceDialog() {
  return openDialog({
    title: "New Announcement",
    description: "Send a short update to the selected audience.",
    icon: "megaphone",
    compact: true,
    body: `
      <div class="announce-fields">
        <div>
          <label class="label" for="announce-message">Message</label>
          <textarea id="announce-message" name="message" rows="5" class="input announce-message" placeholder="Type your announcement…" required></textarea>
        </div>
        <div>
          <label class="label" for="announce-target">Target</label>
          ${Select({ id: "announce-target", value: "role:admin", options: TARGETS })}
        </div>
      </div>
    `,
    footer: `
      <div class="flex justify-end gap-2">
        ${Button({ text: "Cancel", variant: "ghost", attrs: 'data-act="close"' })}
        ${Button({ text: "Send", variant: "default", attrs: 'id="announce-send"' })}
      </div>
    `,
    onMount: (bodyEl, { overlay, close }) => {
      initSelect(bodyEl);
      const sendBtn = overlay.querySelector("#announce-send");
      if (!sendBtn) return;
      sendBtn.addEventListener("click", async () => {
        const message = bodyEl.querySelector('[name="message"]')?.value?.trim();
        const targetValue = getSelectValue(bodyEl.querySelector("#announce-target")) || "role:admin";
        if (!message) {
          toast("Please enter a message", { type: "error" });
          return;
        }
        sendBtn.disabled = true;
        try {
          await broadcastAnnouncement({ type: "admin_announcement", targets: [targetValue], message });
          toast("Announcement sent", { type: "success" });
          close();
          const count = await fetchUnreadCount();
          setNavBadge("notifications", count);
        } catch (e) {
          sendBtn.disabled = false;
          toast(e.message || "Failed to send", { type: "error" });
        }
      });
    },
  });
}

export function initAnnounceDialog() {
  const btn = document.getElementById("announce-btn");
  if (!btn || btn.dataset.announceBound) return;
  btn.dataset.announceBound = "1";
  btn.addEventListener("click", (e) => {
    e.preventDefault();
    AnnounceDialog();
  });
}
