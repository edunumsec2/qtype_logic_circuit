interface Window {

    Logic: {
        getAllComponentTypes(lang: string): Array<{sectionId: string, sectionName: string, components: Array<{id: string, name: string, icon: string}>}>;
    };

}

function define(dependencies: string[], callback: (...args: unknown[]) => void): void;