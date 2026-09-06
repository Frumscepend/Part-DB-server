/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2026 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import {Controller} from "@hotwired/stimulus";
import {Modal} from "bootstrap";
import {Html5Qrcode, Html5QrcodeScanner} from "@part-db/html5-qrcode";

/* stimulusFetch: 'lazy' */

export default class extends Controller {
    static targets = ["input", "modal", "reader", "warning"];

    connect() {
        this._scanner = null;
        this._stopping = null;
        this._modal = new Modal(this.modalTarget);
        this._onShown = () => this._startScanner();
        this._onHidden = () => this._stopScanner();

        this.modalTarget.addEventListener("shown.bs.modal", this._onShown);
        this.modalTarget.addEventListener("hidden.bs.modal", this._onHidden);
    }

    disconnect() {
        this.modalTarget.removeEventListener("shown.bs.modal", this._onShown);
        this.modalTarget.removeEventListener("hidden.bs.modal", this._onHidden);
        this._stopScanner();
        this._modal.dispose();
    }

    open() {
        this._modal.show();
    }

    async _startScanner() {
        if (this._scanner) {
            return;
        }

        if (this._stopping) {
            await this._stopping;
        }

        if (!this.modalTarget.classList.contains("show")) {
            return;
        }

        this.warningTarget.classList.add("d-none");
        Html5Qrcode.getCameras().then((cameras) => {
            if (cameras.length === 0) {
                this.warningTarget.classList.remove("d-none");
            }
        }).catch(() => this.warningTarget.classList.remove("d-none"));

        this._scanner = new Html5QrcodeScanner(this.readerTarget.id, {
            fps: 10,
            qrbox: (width, height) => {
                const size = Math.floor(Math.min(width, height) * 0.7);

                return {width: size, height: size};
            },
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true,
            },
        }, false);

        this._scanner.render((decodedText) => this._handleScan(decodedText));
    }

    _handleScan(decodedText) {
        if (!decodedText || String(decodedText).trim() === "") {
            return;
        }

        this.inputTarget.value = decodedText;
        this.inputTarget.dispatchEvent(new Event("input", {bubbles: true}));
        this.inputTarget.dispatchEvent(new Event("change", {bubbles: true}));
        this._modal.hide();
    }

    _stopScanner() {
        const scanner = this._scanner;
        this._scanner = null;

        if (!scanner) {
            return;
        }

        try {
            this._stopping = Promise.resolve(scanner.clear())
                .catch(() => {})
                .finally(() => {
                    this._stopping = null;
                });
        } catch (_) {
            this._stopping = null;
        }
    }
}
