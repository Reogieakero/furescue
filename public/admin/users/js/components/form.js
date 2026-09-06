import { createIcons, icons } from "lucide";
import { Button } from "/shared/components/button/button.js";
import { Spinner } from "/shared/components/spinner/spinner.js";
import { confirmDialog } from "/shared/components/dialog/dialog.js";
import { createUser, updateUser } from "/assets/js/admin/admin-data.js";
import { currentUserId } from "../state.js";
import { esc, roleLabel, statusLabel, tabsHtml } from "./util.js";

const ROLES = [
  { value: "resident", label: "Resident" },
  { value: "rescuer", label: "Rescuer" },
  { value: "admin", label: "Admin" },
];

const STATUSES = [
  { value: "active", label: "Active" },
  { value: "pending", label: "Pending" },
  { value: "suspended", label: "Suspended" },
  { value: "rejected", label: "Rejected" },
];

function validate(fields, mode) {
  if (!fields.full_name) return "Full name is required.";
  if (fields.full_name.length > 150) return "Full name must be 150 characters or fewer.";
  if (!fields.email) return "Email is required.";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email)) return "Enter a valid email.";
  if (mode === "create" && !fields.password) return "Password is required.";
  if (fields.password && fields.password.length < 8) return "Password must be at least 8 characters.";
  if (fields.phone_number && fields.phone_number.length > 20) return "Phone number is too long.";
  if (fields.address && fields.address.length > 1000) return "Address is too long.";
  return "";
}

function readFields(overlay, form) {
  return {
    full_name: overlay.querySelector("#user-name")?.value.trim() || "",
    email: overlay.querySelector("#user-email")?.value.trim() || "",
    password: overlay.querySelector("#user-password")?.value || "",
    phone_number: overlay.querySelector("#user-phone")?.value.trim() || "",
    address: overlay.querySelector("#user-address")?.value.trim() || "",
    role: form.role,
    account_status: form.status,
  };
}

function toPayload(fields, mode) {
  const body = {
    full_name: fields.full_name,
    email: fields.email,
    phone_number: fields.phone_number || null,
    address: fields.address || null,
    role: fields.role,
    account_status: fields.account_status,
  };
  if (mode === "create" || fields.password) body.password = fields.password;
  return body;
}

export function openUserForm({ mode = "create", user = null } = {}) {
  const isEdit = mode === "edit" && user;
  const isSelf = isEdit && user.id === currentUserId();
  const form = {
    role: (user && user.role) || "resident",
    status: (user && user.account_status) || "active",
  };

  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog dialog--wide" role="dialog" aria-modal="true" aria-labelledby="user-form-title">
        <div class="dialog-head">
          <div class="dialog-heading">
            <div class="dialog-title-wrap">
              <i data-lucide="${isEdit ? "pencil" : "user-plus"}" class="dialog-icon"></i>
              <h3 class="dialog-title" id="user-form-title">${isEdit ? "Edit user" : "Create user"}</h3>
            </div>
            <p class="dialog-desc">${isEdit ? "Update this account. Leave password blank to keep the current one." : "Add a new account. They can sign in with the password you set."}</p>
          </div>
          <button type="button" class="dialog-x" aria-label="Close" data-act="close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          <form id="user-form" class="dialog-form" autocomplete="off">
            <label class="dialog-label" for="user-name">Full name <span class="dialog-req">*</span>
              <input class="dialog-input" id="user-name" name="full_name" maxlength="150" value="${esc(user?.full_name || "")}" autocomplete="off" />
            </label>
            <label class="dialog-label" for="user-email">Email <span class="dialog-req">*</span>
              <input class="dialog-input" id="user-email" name="email" type="email" maxlength="150" value="${esc(user?.email || "")}" autocomplete="off" />
            </label>
            <label class="dialog-label" for="user-password">${isEdit ? "New password" : "Password"} ${isEdit ? "" : '<span class="dialog-req">*</span>'}
              <input class="dialog-input" id="user-password" name="password" type="password" minlength="8" placeholder="${isEdit ? "Leave blank to keep current" : "At least 8 characters"}" autocomplete="new-password" />
            </label>
            <div class="dialog-row">
              <label class="dialog-label" for="user-phone">Phone
                <input class="dialog-input" id="user-phone" name="phone_number" maxlength="20" value="${esc(user?.phone_number || "")}" autocomplete="off" />
              </label>
              <label class="dialog-label" for="user-address">Address
                <input class="dialog-input" id="user-address" name="address" maxlength="1000" value="${esc(user?.address || "")}" autocomplete="off" />
              </label>
            </div>
            <div>
              <span class="dialog-label">Role ${isSelf ? "<span class=\"dialog-hint\">you cannot change your own role</span>" : ""}</span>
              <div class="q-tabs" id="user-role-tabs">${tabsHtml("role", ROLES.map((r) => ({ ...r, disabled: isSelf })), form.role)}</div>
            </div>
            <div>
              <span class="dialog-label">Status ${isSelf ? "<span class=\"dialog-hint\">you cannot change your own status</span>" : ""}</span>
              <div class="q-tabs" id="user-status-tabs">${tabsHtml("status", STATUSES.map((s) => ({ ...s, disabled: isSelf })), form.status)}</div>
            </div>
            <p class="dialog-error" id="user-form-error" hidden></p>
          </form>
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="close"' })}
          ${Button({
            text: isEdit ? "Save changes" : "Create user",
            variant: "default",
            icon: isEdit ? "check" : "user-plus",
            attrs: 'data-act="ok"',
          })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });

    const errorEl = overlay.querySelector("#user-form-error");
    const okBtn = overlay.querySelector('[data-act="ok"]');
    const confirmText = isEdit ? "Save changes" : "Create user";
    let settled = false;

    const close = (value = false) => {
      if (settled) return;
      settled = true;
      overlay.remove();
      document.removeEventListener("keydown", onKey);
      resolve(value);
    };

    const onKey = (e) => {
      if (e.key === "Escape") close(false);
    };

    const activate = (container, attr, value) =>
      container.querySelectorAll(".q-btn").forEach((b) => b.classList.toggle("is-active", b.dataset[attr] === value));

    overlay.querySelector("#user-role-tabs").addEventListener("click", (e) => {
      const btn = e.target.closest("[data-role]");
      if (!btn || btn.disabled) return;
      form.role = btn.dataset.role;
      activate(overlay.querySelector("#user-role-tabs"), "role", form.role);
    });
    overlay.querySelector("#user-status-tabs").addEventListener("click", (e) => {
      const btn = e.target.closest("[data-status]");
      if (!btn || btn.disabled) return;
      form.status = btn.dataset.status;
      activate(overlay.querySelector("#user-status-tabs"), "status", form.status);
    });

    const restore = () => {
      okBtn.disabled = false;
      okBtn.innerHTML = `<span>${esc(confirmText)}</span>`;
    };

    const submit = async () => {
      if (okBtn.disabled) return;
      const fields = readFields(overlay, form);
      const error = validate(fields, isEdit ? "edit" : "create");
      if (error) {
        errorEl.textContent = error;
        errorEl.hidden = false;
        return;
      }
      errorEl.hidden = true;
      const payload = toPayload(fields, isEdit ? "edit" : "create");
      if (isSelf) {
        delete payload.role;
        delete payload.account_status;
      }
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>${esc(confirmText)}</span>`;
      createIcons({ icons });
      const ok = await confirmDialog({
        title: isEdit ? "Save changes to this user?" : "Create this user?",
        message: isEdit
          ? `Save changes to ${fields.full_name}?`
          : `Create ${fields.full_name} as a ${roleLabel(fields.role)} with ${statusLabel(fields.account_status)} access?`,
        info: [
          { label: "Email", value: fields.email },
          { label: "Role", value: roleLabel(isSelf ? user.role : fields.role) },
        ],
        confirmText: isEdit ? "Save changes" : "Create user",
        run: () => (isEdit ? updateUser(user.id, payload) : createUser(payload)),
      });
      if (!ok) {
        restore();
        return;
      }
      close(true);
    };

    overlay.querySelector("#user-form").addEventListener("submit", (e) => {
      e.preventDefault();
      submit();
    });
    okBtn.addEventListener("click", submit);
    overlay.querySelectorAll('[data-act="close"]').forEach((el) => el.addEventListener("click", () => close(false)));
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close(false);
    });
    document.addEventListener("keydown", onKey);
    overlay.querySelector("#user-name")?.focus();
  });
}
