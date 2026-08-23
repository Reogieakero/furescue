import { openDrawer, closeDrawer } from "../../../../../js/components/ui/drawer.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../../js/components/ui/select.js";
import { toast } from "../../../../../js/components/ui/toast.js";
import { broadcastAnnouncement, fetchUnreadCount } from "../../../lib/admin-data.js";
import { setNavBadge } from "../../../../../js/lib/swr.js";

export function AnnounceDialog() {
  const targets = [
    { value: "role:admin", label: "Admins" },
    { value: "role:rescuer", label: "Rescuers" },
    { value: "role:resident", label: "Residents" },
    { value: "all", label: "All users" },
  ];
  return openDrawer({
    title: "New Announcement",
    body: `
      <div class="space-y-3">
        <div>
          <label class="label">Message</label>
          <textarea name="message" rows="4" class="input" placeholder="Type your announcement…" required></textarea>
        </div>
        <div>
          <label class="label">Target</label>
          ${Select({ id: "announce-target", value: "role:admin", options: targets })}
        </div>
      </div>
    `,
    footer: `
      <div class="flex justify-end gap-2">
        ${Button({ text: "Cancel", variant: "ghost", attrs: 'data-act="close"' })}
        ${Button({ text: "Send", variant: "default", attrs: 'id="announce-send"' })}
      </div>
    `,
    onMount: (bodyEl) => {
      initSelect(bodyEl);
      const sendBtn = bodyEl.querySelector("#announce-send");
      if (!sendBtn) return;
      sendBtn.addEventListener("click", async () => {
        const message = bodyEl.querySelector('[name="message"]')?.value?.trim();
        const targetEl = bodyEl.querySelector('[data-select-value]');
        const target = targetEl ? targetEl.textContent?.trim() : "role:admin";
        const targetValue = targets.find((t) => t.label === target)?.value || "role:admin";
        if (!message) {
          toast("Please enter a message", { type: "error" });
          return;
        }
        try {
          await broadcastAnnouncement({ type: "admin_announcement", targets: [targetValue], message });
          toast("Announcement sent", { type: "success" });
          closeDrawer();
          const count = await fetchUnreadCount();
          setNavBadge("notifications", count);
        } catch (e) {
          toast(e.message || "Failed to send", { type: "error" });
        }
      });
    },
  });
}

export function initAnnounceDialog() {
  const btn = document.getElementById("announce-btn");
  if (!btn) return;
  btn.addEventListener("click", (e) => {
    e.preventDefault();
    AnnounceDialog();
  });
}
