export default function calendarComponent({ wire }) {
    return {
        wire: wire,
        events: wire.$entangle('events'),
        calendar: null,

        init() {
            this.initCalendar();
            // this.loadEvents();
            // this.listenForUpdates();
        },

        initCalendar() {
            this.calendar = new tui.Calendar('#calendar', {
                defaultView: 'week',
                taskView: false,
                scheduleView: ['time'],
                useDetailPopup: true,
                useCreationPopup: true,
            });

            this.calendar.on('beforeCreateSchedule', (event) => {
                const newEvent = {
                    title: event.title,
                    start: event.start.toDate(),
                    end: event.end.toDate(),
                    category: 'time'
                };
                this.wire.call('addEvent', newEvent);
            });
        },

        // loadEvents() {
        //     this.events.forEach(event => {
        //         this.calendar.createSchedules([event]);
        //     });
        // },
        //
        // listenForUpdates() {
        //     this.wire.on('calendarUpdated', events => {
        //         this.calendar.clear();
        //         events.forEach(event => {
        //             this.calendar.createSchedules([event]);
        //         });
        //     });
        // }
    };
}
