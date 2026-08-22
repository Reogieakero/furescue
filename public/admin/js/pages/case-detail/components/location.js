import { openLocationDrawer } from "../../../lib/location-drawer.js";
import { state } from "../state.js";

export function renderLocation(caseData) {
  const report = state.report || caseData.report;
  openLocationDrawer({
    lat: report ? report.latitude : null,
    lng: report ? report.longitude : null,
    address: report ? report.address_text : null,
    title: "Case location",
  });
}
