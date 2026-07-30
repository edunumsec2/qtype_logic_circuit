interface LogicStatic {
    getAllComponentTypes(lang: string): Array<{ sectionId: string, sectionName: string, components: Array<{ id: string, name: string, icon: string }> }>
}

interface Window {

    Logic: LogicStatic

}

function define(dependencies: string[], callback: (...args: unknown[]) => void): void