<script>
    function onStart() {
        window.dispatchEvent(new CustomEvent('item-is-moving'));
        setTimeout(() => document.body.classList.add("grabbing"));
    }

    function onEnd() {
        document.body.classList.remove("grabbing")
        setTimeout(() => window.dispatchEvent(new CustomEvent('item-stopped-moving')),
            1000) //await for livewire request
    }

    function setData(dataTransfer, el) {
        dataTransfer.setData('id', el.id)
    }

    function onAdd(e) {
        const recordId = e.item.id
        const status = e.to.dataset.statusId
        const fromOrderedIds = [...e.from.children].map(child => child.id)
        // const fromOrderedIds = [].slice.call(e.from.children).map(child => child.id)
        // const toOrderedIds = [].slice.call(e.to.children).map(child => child.id)
        const toOrderedIds = [...e.to.children].map(child => child.id)

        Livewire.dispatch('status-changed', {
            recordId,
            status,
            fromOrderedIds,
            toOrderedIds
        })


    }


    function onUpdate(e) {

        const recordId = e.item.id
        const status = e.from.dataset.statusId
        const orderedIds = [].slice.call(e.from.children).map(child => child.id)

        Livewire.dispatch('sort-changed', {
            recordId,
            status,
            orderedIds
        })

    }

    document.addEventListener('livewire:navigated', () => {
        const statuses = @js($statuses->map(fn($status) => $status['id']))

        statuses.forEach(status => Sortable.create(document.querySelector(`[data-status-id='${status}']`), {
            group: `filament-kanban`,
            ghostClass: 'draggable-item',
            animation: 150,
            onStart,
            onEnd,
            onUpdate,
            setData,
            onAdd,


        }))

    })
</script>
