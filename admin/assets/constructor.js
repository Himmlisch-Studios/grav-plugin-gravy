
const constructor = {
    parentElement: null,
    selectors: {
        input: "#constructor",
        trash: ".constructor__sidebar__trash",
        grid: ".constructor__wrapper>.grid-stack",
        add: ".constructor__new",
    },
    inputElement: null,
    trashElement: null,
    gridElement: null,
    addElement: null,
    grid: null,
    data: {
        added: [],
        edited: {},
        removed: {},
    },
    init(parent = null) {
        this.initDom(parent ?? document);
        this.initGridStack();
        this.initEvents();
        this.afterInit();
    },
    initDom(parent) {
        this.parentElement = parent;
        this.inputElement = this.parentElement.querySelector(this.selectors.input);
        this.trashElement = this.parentElement.querySelector(this.selectors.trash);
        this.gridElement = this.parentElement.querySelector(this.selectors.grid);
        this.addElement = this.parentElement.querySelector(this.selectors.add);
    },
    initGridStack() {
        this.grid = GridStack.init({
            cellHeight: 150,
            acceptWidgets: true,
            removable: this.selectors.trash
        }, this.gridElement);

        GridStack.setupDragIn(this.selectors.add, { helper: "clone" });
    },
    initEvents() {
        this.grid.on("dragstart", () => this.trashElement.classList.add("open"));
        this.grid.on("dragstop", () => {
            this.trashElement.classList.remove("open");

            this.grid.compact();
            this.sync();
        });

        this.grid.on("resize", (e, el) => {
            this.grid.compact();
            this.sync();

            // Prevents GridStack from tripping sometimes
            this.grid.update(el, { h: 1, maxH: 1 });
        });

        this.grid.on("changed", (e, items) => {
            this.grid.compact();
            this.sync();
        });

        this.grid.on("added", (e, items) => {
            this.grid.compact();

            items.forEach((item) => this.newElement(item));

            this.sync();
        });

        this.grid.on('removed', (event, items) => {
            items.forEach((item) => this.removeElement(item.el));

            this.sync();
        });

        window.addEventListener("beforeunload", (event) => {
            this.cleanup();
            this.syncInput();
        });
    },
    afterInit() {
        if (this.gridElement.style.display === 'none') {
            this.gridElement.style.display = "block";
        }

        this.syncGridStack();
    },
    sync() {
        this.syncGridStack();
        this.syncInput();
    },
    syncGridStack() {
        this.grid.getGridItems().forEach((el) => {
            if (typeof el.gridstackNode.id == typeof '') {
                this.data.edited[el.gridstackNode.id] = this.parseElement(el);
            }
        });
    },
    syncInput() {
        this.inputElement.value = JSON.stringify(this.data);
    },
    newElement(item) {
        item.el.gridstackNode.id = this.data.added.length;

        const element = this.parseElement(item.el);

        this.data.added.push(element);
    },
    removeElement(el) {
        const element = this.parseElement(el);

        if (typeof element.id !== typeof '') {
            this.data.added[element.id] = null;
            return;
        }

        this.data.removed[element.id] = element;
    },
    parseElement(el) {
        const node = el.gridstackNode;

        return {
            id: node.id,
            name: el.dataset.name,
            template: el.dataset.template,
            x: node?.x ?? 0,
            y: node?.y ?? 0,
            w: node?.w ?? 12,
        };
    },
    cleanup() {
        this.sync();
        this.data.added = this.data.added.filter((v) => v);
    },
};

// Small dalay since Grav Admin does some strange
// things with the screen that makes GridStack
// not work properly
setTimeout(() => constructor.init(), 1000);